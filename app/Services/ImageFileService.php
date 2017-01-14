<?php
namespace App\Services;


use Illuminate\Filesystem\Filesystem;

class ImageFileService
{

    /**
     * @type Filesystem
     */
    private $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * @param $directory
     * @param $imageData
     * @return string
     */
    public function store($directory, $imageData)
    {
        if ($this->isUrl($imageData)) {
            return $imageData;
        }

        $fileName = $this->validImageDataURI($imageData, $directory, true);

        return $fileName;
    }

    /**
     * @param string $imageData
     * @param string $filePath
     */
    private function storeImage($imageData, $filePath)
    {
        $file_data = preg_replace('/^data\:image\/.+\;base64\,/', '', $imageData);
        $file_data = base64_decode($file_data);
        file_put_contents($filePath, $file_data);
    }

    /**
     * @param string $filePath
     */
    private function deleteImage($filePath)
    {
        unlink($filePath);
    }

    /**
     * @param string $imageData
     * @return bool
     */
    public function validateSubmittedImage($imageData)
    {
        if ($this->isUrl($imageData)) {
            return $this->acceptedUrl($imageData);
        }

        return $this->validImageDataURI($imageData);
    }

    /**
     * @param $imageData
     * @return mixed
     */
    public function isUrl($imageData)
    {
        return filter_var($imageData, FILTER_VALIDATE_URL);
    }

    /**
     * @param $imageData
     * @return bool
     */
    protected function acceptedUrl($imageData)
    {
        $url = parse_url($imageData);
        $original = parse_url(config('app.url'));
        $arr = explode('/', $imageData);


        return ($url['host'] == $original['host'] && array_get($url, 'port') == array_get($original, 'port') && count($arr) >= 2 && is_file(public_path('img/uploads/' . $arr[count($arr) - 2] . '/' . $arr[count($arr) - 1])));
    }

    /**
     * @param      $imageData
     * @param null $directoryPath
     * @param bool $keepImage
     * @return bool
     */
    protected function validImageDataURI($imageData, $directoryPath = null, $keepImage = false)
    {
        $directoryPath = $directoryPath?: storage_path();
        if (! preg_match('/^data\:image\/(.+)\;base64\,/', $imageData, $matches)) {
            return false;
        }

        $extension = $matches[1];
        if (strtolower($extension) == 'jpeg') {
            $extension = 'jpg';
        }

        list($fileName, $filePath) = $this->prepareDirectory($directoryPath, $extension);

        $this->storeImage($imageData, $filePath);

        if (! $this->validImage($filePath)) {
            return false;
        }


        if (! $this->validFileSize($filePath)) {
            return false;
        }


        if (! $keepImage) {
            $this->deleteImage($filePath);
        }

        return $keepImage? $fileName : true;
    }

    /**
     * @param $filePath
     * @return bool
     */
    protected function validImage($filePath)
    {
        $imageSize = getimagesize($filePath);
        if (! $imageSize || ! $imageSize['mime'] || ! in_array(strtolower($imageSize['mime']), ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'])) {
            return false;
        }

        return true;
    }

    /**
     * @param $filePath
     * @return bool
     */
    private function validFileSize($filePath)
    {
        $fileSize = filesize($filePath);
        if (! $fileSize || $filePath / 1048576.0 > 5) {
            return false;
        }

        return true;
    }

    /**
     * @param $extension
     * @return string
     */
    protected function randomFileName($extension)
    {
        $fileName = time() . md5(uniqid()) . '.' . $extension;

        return $fileName;
    }

    /**
     * @param $directoryPath
     * @param $fileExtension
     * @return array
     */
    protected function prepareDirectory($directoryPath, $fileExtension)
    {
        $fileName = $this->randomFileName($fileExtension);

        $directoryPath = rtrim($directoryPath, DIRECTORY_SEPARATOR);

        if (! $this->files->exists($directoryPath)) {
            $this->files->makeDirectory($directoryPath, 0775, true);
        }

        $filePath = $directoryPath . DIRECTORY_SEPARATOR . $fileName;

        return [$fileName, $filePath];
    }
}