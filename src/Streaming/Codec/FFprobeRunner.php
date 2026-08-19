<?php

namespace XcVm\Streaming\Codec;
use XcVm\Core\Util\StreamUtils;

/**
 * FFprobeRunner — f fprobe runner
 *
 * @package XC_VM_Streaming_Codec
 * @author  Divarion_D <https://github.com/Divarion-D>
 * @copyright 2025-2026 Vateron Media
 * @link    https://github.com/Vateron-Media/XC_VM
 * @license AGPL-3.0 https://www.gnu.org/licenses/agpl-3.0.html
 */

class FFprobeRunner {
	public static function probeStream($rSourceURL, $rFetchArguments = array(), $rPrepend = '', $rParse = true) {
		global $rSettings, $rFFPROBE;
		$settings = !empty($rSettings) && is_array($rSettings) ? $rSettings : \XcVm\Core\Config\SettingsManager::getAll();
		$rFFPROBE = !empty($rFFPROBE) ? $rFFPROBE : \XcVm\Streaming\Codec\FfmpegPaths::probe();
		$rAnalyseDuration = !empty($settings['stream_max_analyze']) ? abs(intval($settings['stream_max_analyze'])) : 5000000;
		$rProbesize = !empty($settings['probesize']) ? abs(intval($settings['probesize'])) : 5000000;
		$rTimeout = intval($rAnalyseDuration / 1000000) + intval($settings['probe_extra_wait'] ?? 5);
		if ($rTimeout < 5) {
			$rTimeout = 15;
		}
		if (!is_array($rFetchArguments)) {
			$rFetchArguments = !empty($rFetchArguments) ? [$rFetchArguments] : [];
		}

		$rProxy = StreamUtils::extractProxy($rSourceURL, $rFetchArguments);
		if (!empty($rProxy)) {
			$hasProxyArg = false;
			foreach ($rFetchArguments as $arg) {
				if (stripos($arg, '-http_proxy') !== false) {
					$hasProxyArg = true;
					break;
				}
			}
			if (!$hasProxyArg) {
				$rFetchArguments[] = '-http_proxy ' . escapeshellarg($rProxy);
			}
		}

		$rEffectiveURL = StreamUtils::parseStreamURL($rSourceURL, $rProxy);

		$rDecryptionKey = StreamUtils::extractDecryptionKey($rSourceURL);
		if (empty($rDecryptionKey) && $rEffectiveURL !== $rSourceURL) {
			$rDecryptionKey = StreamUtils::extractDecryptionKey($rEffectiveURL);
		}
		if (!empty($rDecryptionKey)) {
			$rFetchArguments[] = '-decryption_key ' . escapeshellarg($rDecryptionKey);
		}

		$rCommand = $rPrepend . 'timeout ' . $rTimeout . ' ' . $rFFPROBE . ' -probesize ' . $rProbesize . ' -analyzeduration ' . $rAnalyseDuration . ' ' . implode(' ', $rFetchArguments) . ' -i ' . escapeshellarg($rEffectiveURL) . ' -v quiet -print_format json -show_streams -show_format';
		exec($rCommand, $rReturn);
		$result = implode("\n", $rReturn);
		if ($rParse) {
			return self::parseFFProbe(json_decode($result, true));
		}
		return json_decode($result, true);
	}

	public static function parseFFProbe($rCodecs) {
		if (empty($rCodecs)) {
			return false;
		}
		if (empty($rCodecs['codecs'])) {
			$rOutput = array();
			$rOutput['codecs']['video'] = '';
			$rOutput['codecs']['audio'] = '';
			$rOutput['container'] = $rCodecs['format']['format_name'];
			$rOutput['filename'] = $rCodecs['format']['filename'];
			$rOutput['bitrate'] = (!empty($rCodecs['format']['bit_rate']) ? $rCodecs['format']['bit_rate'] : null);
			$rOutput['of_duration'] = (!empty($rCodecs['format']['duration']) ? $rCodecs['format']['duration'] : 'N/A');
			$rOutput['duration'] = (!empty($rCodecs['format']['duration']) ? gmdate('H:i:s', intval($rCodecs['format']['duration'])) : 'N/A');
			foreach ($rCodecs['streams'] as $rCodec) {
				if (isset($rCodec['codec_type']) && !($rCodec['codec_type'] != 'audio' && $rCodec['codec_type'] != 'video' && $rCodec['codec_type'] != 'subtitle')) {
					if ($rCodec['codec_type'] == 'audio' || $rCodec['codec_type'] == 'video') {
						if (!empty($rOutput['codecs'][$rCodec['codec_type']])) {
						} else {
							$rOutput['codecs'][$rCodec['codec_type']] = $rCodec;
						}
					} else {
						if ($rCodec['codec_type'] != 'subtitle') {
						} else {
							if (isset($rOutput['codecs'][$rCodec['codec_type']])) {
							} else {
								$rOutput['codecs'][$rCodec['codec_type']] = array();
							}
							$rOutput['codecs'][$rCodec['codec_type']][] = $rCodec;
						}
					}
				}
			}
			return $rOutput;
		}
		return $rCodecs;
	}
}
