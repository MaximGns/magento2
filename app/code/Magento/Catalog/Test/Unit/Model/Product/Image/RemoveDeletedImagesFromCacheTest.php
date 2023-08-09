<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Test\Unit\Model\Product\Image;

use Magento\Catalog\Model\Product\Image\ConvertImageMiscParamsToReadableFormat;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\Product\Image\RemoveDeletedImagesFromCache;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Config\View;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\View\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RemoveDeletedImagesFromCacheTest extends TestCase
{
    /**
     * @var RemoveDeletedImagesFromCache
     */
    protected $model;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $presentationConfig;

    /**
     * @var EncryptorInterface|MockObject
     */
    protected $encryptor;

    /**
     * @var Config|MockObject
     */
    protected $mediaConfig;

    /**
     * @var Write|MockObject
     */
    protected $mediaDirectory;

    /**
     * @var ParamsBuilder|MockObject
     */
    protected $imageParamsBuilder;

    /**
     * @var ConvertImageMiscParamsToReadableFormat|MockObject
     */
    protected $convertImageMiscParamsToReadableFormat;

    /**
     * @var View|MockObject
     */
    protected $viewMock;

    protected function setUp(): void
    {
        $this->presentationConfig = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->encryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mediaConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mediaDirectory = $this->createMock(Write::class);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('getDirectoryWrite')
            ->willReturn($this->mediaDirectory);

        $this->imageParamsBuilder = $this->getMockBuilder(ParamsBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->convertImageMiscParamsToReadableFormat = $this
            ->getMockBuilder(ConvertImageMiscParamsToReadableFormat::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new RemoveDeletedImagesFromCache(
            $this->presentationConfig,
            $this->encryptor,
            $this->mediaConfig,
            $filesystem,
            $this->imageParamsBuilder,
            $this->convertImageMiscParamsToReadableFormat
        );

        $this->viewMock = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $data
     * @dataProvider createDataProvider
     */
    public function testRemoveDeletedImagesFromCache($data)
    {
        $this->presentationConfig->expects($this->once())
            ->method('getViewConfig')
            ->with(['area' => \Magento\Framework\App\Area::AREA_FRONTEND])
            ->willReturn($this->viewMock);

        $this->viewMock->expects($this->once())
            ->method('getMediaEntities')
            ->willReturn([$data['viewImageConfig']]);

        $this->imageParamsBuilder->expects($this->once())
            ->method('build')
            ->willReturn($data['imageParamsBuilder']);

        $this->convertImageMiscParamsToReadableFormat->expects($this->once())
            ->method('convertImageMiscParamsToReadableFormat')
            ->willReturn($data['convertImageParamsToReadableFormat']);

        $this->encryptor->expects($this->once())
            ->method('hash')
            ->willReturn('85b0304775df23c13f08dd2c1f9c4c28');

        $this->mediaConfig->expects($this->once())
            ->method('getBaseMediaPath')
            ->willReturn('catalog/product');

        $this->mediaDirectory->expects($this->once())
            ->method('delete')
            ->willReturn(true);

        $this->model->removeDeletedImagesFromCache(['i/m/image.jpg']);
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            $this->getTestDataWithAttributes()
        ];
    }

    /**
     * @return array
     */
    private function getTestDataWithAttributes(): array
    {
        return [
            'data' => [
                'viewImageConfig' => [
                    'width' => 100,
                    'height' => 50, // <===
                    'constrain_only' => false,
                    'aspect_ratio' => false,
                    'frame' => true, // <===
                    'transparency' => false,
                    'background' => '255,255,255',
                    'type' => 'thumbnail' //thumbnail,small_image,image,swatch_image,swatch_thumb
                ],
                'imageParamsBuilder' => [
                    'image_width' => 100,
                    'image_height' => 50,
                    'constrain_only' => false,
                    'keep_aspect_ratio' => false,
                    'keep_frame' => true,
                    'keep_transparency' => false,
                    'background' => '255,255,255',
                    'image_type' => 'thumbnail', //thumbnail,small_image,image,swatch_image,swatch_thumb
                    'quality' => 80,
                    'angle' => null
                ],
                'convertImageParamsToReadableFormat' => [
                    'image_height' => 'h: 50',
                    'image_width' => 'w: 100',
                    'quality' => 'q: 80',
                    'angle' => 'r: ',
                    'keep_aspect_ratio' => 'non proportional',
                    'keep_frame' => 'no frame',
                    'keep_transparency' => 'no transparency',
                    'constrain_only' => 'not constrainonly',
                    'background' => 'rgb 255,255,255'
                ]
            ]
        ];
    }
}
