<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nurtjahjo\StoremgmCA\Domain\Entity\Product;
use Nurtjahjo\StoremgmCA\Domain\ValueObject\Money;

class ProductClosingDataTest extends TestCase
{
    public function test_product_can_hold_closing_data()
    {
        $product = new Product(
            'uuid-1', 'cat-1', 'id', 'audiobook', 'Judul', 'Sinopsis', 'auth-1', null, null, null, null,
            new Money(10, 'USD'), false, null, null, null, 'published', null, null, null,
            'Terima kasih telah mendengarkan.', // closingText
            'uuid-1_finish.mp3' // closingAudioPath
        );

        $this->assertEquals('Terima kasih telah mendengarkan.', $product->getClosingText());
        $this->assertEquals('uuid-1_finish.mp3', $product->getClosingAudioPath());
        
        $arrayData = $product->toArray();
        $this->assertArrayHasKey('closing_text', $arrayData);
        $this->assertArrayHasKey('closing_audio_path', $arrayData);
        $this->assertEquals('uuid-1_finish.mp3', $arrayData['closing_audio_path']);
    }
    
    public function test_closing_data_is_optional()
    {
        // Test backward compatibility (tanpa argumen penutup)
        $product = new Product(
            'uuid-1', 'cat-1', 'id', 'audiobook', 'Judul', 'Sinopsis', 'auth-1', null, null, null, null,
            new Money(10, 'USD')
        );

        $this->assertNull($product->getClosingText());
        $this->assertNull($product->getClosingAudioPath());
    }
}
