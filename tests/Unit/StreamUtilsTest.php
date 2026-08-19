<?php

use XcVm\Core\Util\StreamUtils;
use PHPUnit\Framework\TestCase;

/**
 * @covers StreamUtils
 */
final class StreamUtilsTest extends TestCase {

	public function testCustomOrderPutsInputArgumentsFirst() {
		$this->assertSame(-1, StreamUtils::customOrder('-i input.ts', 'something'));
		$this->assertSame(1, StreamUtils::customOrder('-c:v libx264', '-i input.ts'));
	}

	public function testDetectXcVmMatchesKnownStreamPaths() {
		$this->assertTrue(StreamUtils::detectXC_VM('http://host/live/user/123'));
	}

	public function testDetectXcVmRejectsUnrelatedPaths() {
		$this->assertFalse(StreamUtils::detectXC_VM('http://host/dashboard'));
	}

	public function testExtractDecryptionKeySingleKidKey() {
		$url = 'http://example.com/live.mpd?decryption_key=e3ce77324a3d4fa2a913b26cc1976052:17774f82a3b9e33ea7a145d5a2d04a6b';
		$this->assertSame('e3ce77324a3d4fa2a913b26cc1976052:17774f82a3b9e33ea7a145d5a2d04a6b', StreamUtils::extractDecryptionKey($url));
	}

	public function testExtractDecryptionKeyRawHex() {
		$url = 'http://example.com/live.mpd?cenc_decryption_key=17774f82a3b9e33ea7a145d5a2d04a6b';
		$this->assertSame('17774f82a3b9e33ea7a145d5a2d04a6b', StreamUtils::extractDecryptionKey($url));
	}

	public function testExtractDecryptionKeyMultiKidKeys() {
		$url = 'http://example.com/live.mpd?decryption_key=8c8ace025d911c6f45b7678ff44b1a73:d59729c1b1b8ec64be8534405d22b319,430fe9f0dd03466a23d57f4c2fe5e392:91f8b7b70bb9942b580c906a69833d76,3fc81690979f0514dbb4bc6f082223a0:c8a0254cd5ac5faad78920c4dfe0293d';
		$expected = '8c8ace025d911c6f45b7678ff44b1a73:d59729c1b1b8ec64be8534405d22b319,430fe9f0dd03466a23d57f4c2fe5e392:91f8b7b70bb9942b580c906a69833d76,3fc81690979f0514dbb4bc6f082223a0:c8a0254cd5ac5faad78920c4dfe0293d';
		$this->assertSame($expected, StreamUtils::extractDecryptionKey($url));

		$keysArray = StreamUtils::extractCencKeys($url);
		$this->assertCount(3, $keysArray);
		$this->assertSame('8c8ace025d911c6f45b7678ff44b1a73:d59729c1b1b8ec64be8534405d22b319', $keysArray[0]);
		$this->assertSame('430fe9f0dd03466a23d57f4c2fe5e392:91f8b7b70bb9942b580c906a69833d76', $keysArray[1]);
		$this->assertSame('3fc81690979f0514dbb4bc6f082223a0:c8a0254cd5ac5faad78920c4dfe0293d', $keysArray[2]);
	}

	public function testExtractDecryptionKeyPipeSyntax() {
		$url = 'http://example.com/live.mpd|decryption_key=8c8ace025d911c6f45b7678ff44b1a73:d59729c1b1b8ec64be8534405d22b319';
		$this->assertSame('8c8ace025d911c6f45b7678ff44b1a73:d59729c1b1b8ec64be8534405d22b319', StreamUtils::extractDecryptionKey($url));
	}

	public function testExtractProxyFromUrlAndArguments() {
		$url = 'http://example.com/live.mpd?proxy=http://user:pass@proxy.example.com:8080';
		$this->assertSame('http://user:pass@proxy.example.com:8080', StreamUtils::extractProxy($url));

		$urlPipe = 'http://example.com/live.mpd|http_proxy=http://proxy.example.com:3128';
		$this->assertSame('http://proxy.example.com:3128', StreamUtils::extractProxy($urlPipe));

		$args = ["-user_agent 'Mozilla/5.0'", "-http_proxy 'http://proxy.example.com:8080'"];
		$this->assertSame('http://proxy.example.com:8080', StreamUtils::extractProxy('http://example.com/live.mpd', $args));
	}
}
