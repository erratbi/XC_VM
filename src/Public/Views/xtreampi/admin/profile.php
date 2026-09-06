<?php
require_once __DIR__ . '/_migration_helpers.php';

/* ProfileService consumes a flat POST payload but stores most values in the
 * profile_options JSON object. Flatten the controller's legacy shape here so
 * editing a profile round-trips its existing values instead of presenting a
 * blank form. */
$editing = is_array($rProfileArr ?? null);
$row = $editing ? $rProfileArr : [];
$options = is_array($rProfileOptions ?? null) ? $rProfileOptions : [];
$optionValue = static function ($key, $default = '') use ($options) {
    $value = $options[$key] ?? $default;
    return is_array($value) ? ($value['val'] ?? $default) : $value;
};
$gpu = is_array($options['gpu'] ?? null) ? $options['gpu'] : [];
$codec = (string) $optionValue('-vcodec');
$gpuCodec = in_array($codec, ['h264_nvenc', 'hevc_nvenc'], true) ? $codec : '';
$cpuCodec = $gpuCodec === '' ? $codec : '';
$codecFamily = $codec === 'hevc_nvenc' ? 'hevc' : 'h264';
$row['profile_name'] = $row['profile_name'] ?? '';
$row['gpu_device'] = $gpu['val'] ?? 0;
$row['software_decoding'] = (int) ($options['software_decoding'] ?? 0);
$row['video_codec_cpu'] = $cpuCodec;
$row['video_codec_gpu'] = $gpuCodec;
$row['audio_codec'] = $optionValue('-acodec');
$row['preset_cpu'] = $gpuCodec === '' ? $optionValue('-preset') : '';
$row['preset_h264'] = $codecFamily === 'h264' ? $optionValue('-preset') : '';
$row['preset_hevc'] = $codecFamily === 'hevc' ? $optionValue('-preset') : '';
$row['video_profile_cpu'] = $gpuCodec === '' ? $optionValue('-profile:v') : '';
$row['video_profile_h264'] = $codecFamily === 'h264' ? $optionValue('-profile:v') : '';
$row['video_profile_hevc'] = $codecFamily === 'hevc' ? $optionValue('-profile:v') : '';
$row['resize'] = $gpu['resize'] ?? '';
$row['deint'] = (int) ($gpu['deint'] ?? ($options[17]['val'] ?? 0));
$row['scaling'] = $optionValue(9);
$row['yadif_filter'] = (int) ($options[17]['val'] ?? 0);
foreach ([3 => 'video_bitrate', 4 => 'audio_bitrate', 5 => 'min_tolerance', 6 => 'max_tolerance', 7 => 'buffer_size', 8 => 'crf_value', 10 => 'aspect_ratio', 11 => 'framerate', 12 => 'samplerate', 13 => 'audio_channels', 15 => 'threads'] as $key => $name) {
    $row[$name] = $optionValue($key);
}
$row['logo_path'] = $optionValue(16);
$row['logo_pos'] = is_array($options[16] ?? null) ? ($options[16]['pos'] ?? '10:10') : '10:10';

$devices = is_array($rDevices ?? null) ? $rDevices : ['Off'];
$fields = [
    ['name' => 'profile_name', 'label' => 'Profile name', 'required' => true],
    ['name' => 'gpu_device', 'label' => 'GPU device', 'type' => 'select', 'options' => $devices, 'default' => 0],
    ['name' => 'software_decoding', 'label' => 'Software decoding', 'type' => 'select', 'options' => [0 => 'Hardware decoding', 1 => 'Software decoding']],
    ['name' => 'video_codec_cpu', 'label' => 'CPU video codec'],
    ['name' => 'video_codec_gpu', 'label' => 'GPU video codec'],
    ['name' => 'audio_codec', 'label' => 'Audio codec'],
    ['name' => 'preset_cpu', 'label' => 'CPU preset'],
    ['name' => 'video_profile_cpu', 'label' => 'CPU video profile'],
    ['name' => 'preset_h264', 'label' => 'H.264 preset'],
    ['name' => 'video_profile_h264', 'label' => 'H.264 profile'],
    ['name' => 'preset_hevc', 'label' => 'HEVC preset'],
    ['name' => 'video_profile_hevc', 'label' => 'HEVC profile'],
    ['name' => 'resize', 'label' => 'GPU resize'],
    ['name' => 'deint', 'label' => 'GPU deinterlace', 'type' => 'select', 'options' => [0 => 'Weave (default)', 1 => 'Bob', 2 => 'Adaptive']],
    ['name' => 'scaling', 'label' => 'Scaling'],
    ['name' => 'yadif_filter', 'label' => 'Yadif filter', 'type' => 'checkbox'],
    ['name' => 'video_bitrate', 'label' => 'Video bitrate'],
    ['name' => 'audio_bitrate', 'label' => 'Audio bitrate'],
    ['name' => 'min_tolerance', 'label' => 'Minimum bitrate tolerance'],
    ['name' => 'max_tolerance', 'label' => 'Maximum bitrate tolerance'],
    ['name' => 'buffer_size', 'label' => 'Buffer size'],
    ['name' => 'crf_value', 'label' => 'CRF value'],
    ['name' => 'framerate', 'label' => 'Framerate'],
    ['name' => 'samplerate', 'label' => 'Audio sample rate'],
    ['name' => 'audio_channels', 'label' => 'Audio channels'],
    ['name' => 'threads', 'label' => 'Threads'],
    ['name' => 'aspect_ratio', 'label' => 'Aspect ratio'],
    ['name' => 'logo_path', 'label' => 'Logo path'],
    ['name' => 'logo_pos', 'label' => 'Logo position', 'default' => '10:10'],
];
sc_m_editor($editing ? 'Edit transcoding profile' : 'Add transcoding profile', 'post.php?action=profile&referer=profiles', 'profiles', $row, $fields, 'submit_profile', $editing ? 'Edit' : 'Add');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
