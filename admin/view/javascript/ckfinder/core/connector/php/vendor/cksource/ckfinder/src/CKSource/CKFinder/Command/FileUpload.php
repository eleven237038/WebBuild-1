<?php

namespace CKSource\CKFinder\Command;

use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Cache\CacheManager;
use CKSource\CKFinder\Event\AfterCommandEvent;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Config;
use CKSource\CKFinder\Error;
use CKSource\CKFinder\Exception\InvalidExtensionException;
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Exception\InvalidUploadException;
use CKSource\CKFinder\Filesystem\File\UploadedFile;
use CKSource\CKFinder\Event\FileUploadEvent;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Thumbnail\ThumbnailRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class FileUpload extends CommandAbstract
{
    protected $requires = array(Permission::FILE_UPLOAD);

    public function execute(Request $request, WorkingFolder $workingFolder, EventDispatcher $dispatcher, Config $config, CacheManager $cache, ThumbnailRepository $thumbsRepository)
    {
        // #111 IE9 download JSON issue workaround
        if ($request->get('asPlainText')) {
            $uploadEvents = array(
                CKFinderEvent::AFTER_COMMAND_FILE_UPLOAD,
                CKFinderEvent::AFTER_COMMAND_QUICK_UPLOAD
            );

            foreach ($uploadEvents as $eventName) {
                $dispatcher->addListener($eventName, function(AfterCommandEvent $event) {
                    $response = $event->getResponse();
                    $response->headers->set('Content-Type', 'text/plain');
                });
            }
        }

        $uploaded = 0;

        $warningErrorCode = null;
        $upload = $request->files->get('upload');
		//throw new InvalidUploadException();
        if (null === $upload) {
            throw new InvalidUploadException();
        }

        $uploadedFile = new UploadedFile($upload, $this->app);

        if (!$uploadedFile->isValid()) {
            throw new InvalidUploadException($uploadedFile->getErrorMessage());
        }

        $uploadedFile->sanitizeFilename();

        if ($uploadedFile->wasRenamed()) {
            $warningErrorCode = Error::UPLOADED_INVALID_NAME_RENAMED;
        }

        if (!$uploadedFile->hasValidFilename() || $uploadedFile->isHiddenFile()) {
            throw new InvalidNameException();
        }

        if (!$uploadedFile->hasAllowedExtension()) {
            throw new InvalidExtensionException();
        }

        // Autorename if required
        $overwriteOnUpload = $config->get('overwriteOnUpload');
        if (!$overwriteOnUpload && $uploadedFile->autorename()) {
            $warningErrorCode = Error::UPLOADED_FILE_RENAMED;
        }

        $fileName = $uploadedFile->getFilename();
		$file_arr = explode('.', $fileName);
		$file_suffix = $file_arr[count($file_arr) - 1]; //后缀
		$file_name_arr = array_slice($file_arr, 0, count($file_arr) - 1);
		$file_name = implode('.', $file_name_arr);//文件名
		if(preg_match('/([\x81-\xfe][\x40-\xfe])/', $file_name)){
            $prefix = preg_replace('/\.+/', '_', preg_replace('/\s+/', '', time() . microtime()));
		}else{
			$fileName = $file_name . '.' . $file_suffix;
		}

        if (!$uploadedFile->isAllowedHtmlFile() && $uploadedFile->containsHtml()) {
            throw new InvalidUploadException('HTML detected in disallowed file type', Error::UPLOADED_WRONG_HTML_FILE);
        }

        if ($config->get('secureImageUploads') && $uploadedFile->isImage() && !$uploadedFile->isValidImage()) {
            throw new InvalidUploadException('Invalid upload: corrupted image', Error::UPLOADED_CORRUPT);
        }

        $maxFileSize = $workingFolder->getResourceType()->getMaxSize();

        if (!$config->get('checkSizeAfterScaling') && $maxFileSize && $uploadedFile->getSize() > $maxFileSize) {
            throw new InvalidUploadException('Uploaded file is too big', Error::UPLOADED_TOO_BIG);
        }

        if (Image::isSupportedExtension($uploadedFile->getExtension())) {
            $imagesConfig = $config->get('images');
            $image = Image::create($uploadedFile->getContents());

            if ($image->getWidth() > $imagesConfig['maxWidth'] || $image->getHeight() > $imagesConfig['maxHeight']) {
                $image->resize($imagesConfig['maxWidth'], $imagesConfig['maxHeight'], $imagesConfig['quality']);
                $imageData = $image->getData();
                $uploadedFile->setContents($imageData);
            }

            $cache->set(
                Path::combine(
                    $workingFolder->getResourceType()->getName(),
                    $workingFolder->getClientCurrentFolder(),
                    $fileName),
                $image->getInfo()
            );

            unset($imageData);
            unset($image);
        }

        if ($maxFileSize && $uploadedFile->getSize() > $maxFileSize) {
            throw new InvalidUploadException('Uploaded file is too big', Error::UPLOADED_TOO_BIG);
        }

        $event = new FileUploadEvent($this->app, $uploadedFile);
        $dispatcher->dispatch(CKFinderEvent::FILE_UPLOAD, $event);

        if (!$event->isPropagationStopped()) {
            $uploadedFileStream = $uploadedFile->getContentsStream();
            $uploaded = (int) $workingFolder->putStream($fileName, $uploadedFileStream);

            if ($overwriteOnUpload) {
                $thumbsRepository->deleteThumbnails(
                    $workingFolder->getResourceType(),
                    $workingFolder->getClientCurrentFolder(),
                    $fileName
                );
            }

            if (!$uploaded) {
                $warningErrorCode = Error::ACCESS_DENIED;
            }
        }

        $responseData = array(
            'fileName' => $fileName,
            'uploaded' => $uploaded
        );

        if ($warningErrorCode) {
            $errorMessage = $this->app['translator']->translateErrorMessage($warningErrorCode, array($fileName));
            $responseData['error'] = array(
                'number'  => $warningErrorCode,
                'message' => $errorMessage
            );
        }

        /** watermark start  */
        $currentFile = $_GET['currentFolder'] . $fileName;

        session_start();
        $session = $_SESSION;
        $session_keys = array_keys($session);
        $image_base_path =  $session[$session_keys[0]]['image_base_path'];
        $water_data =  $session[$session_keys[0]]['water_data'];
        if($water_data['status']){
            if($water_data['type'] == '1'){
                $this->waterImageAlpha($image_base_path . 'catalog' . $currentFile, $image_base_path . '/' . $water_data['img'], $water_data['alpha'], $water_data['pos']);
            }elseif($water_data['type'] == '2'){
                $this->waterFont($image_base_path . 'catalog' . $currentFile, $water_data['font'], $water_data['font_src'], $water_data['font_color'], $water_data['pos'], $water_data['alpha']);
            }
        }
        /** watermark end  */

        return $responseData;
    }
    protected function waterImageAlpha($image, $water, $alpha = 90, $pos = 'lt'){
        $dst = $image;
        $dst_info = getimagesize($dst); 
        $img_size = $this->getImgSize($image);
        $image_width = $img_size['width'];
        $image_height = $img_size['height'];
        if($image_width >= 300 || $image_height >= 300){
            switch ($dst_info[2]) { 
                case 1: 
                    $dst_im =imagecreatefromgif($dst);
                    break; 
                case 2: 
                    $dst_im =imagecreatefromjpeg($dst);
                    break; 
                case 3: 
                    $dst_im =imagecreatefrompng($dst);
                    break; 
                case 6: 
                    $dst_im =imagecreatefromwbmp($dst);
                    break; 
            } 
            $src = $water;
            $src_info = getimagesize($src); 
            switch ($src_info[2]) { 
                case 1: 
                    $src_im =imagecreatefromgif($src);
                    break; 
                case 2: 
                    $src_im =imagecreatefromjpeg($src);
                    break; 
                case 3: 
                    $src_im =imagecreatefrompng($src);
                    break; 
                case 6: 
                    $src_im =imagecreatefromwbmp($src);
                    break; 
            }
            $offsetX = 0;
            $offsetY = 0;
            switch($pos){
                case 'lt':
                    $offsetX = 0;
                    $offsetY = -10;
                    break;
                case 'rt':
                    $offsetX = $dst_info[0]-$src_info[0] - 10;
                    $offsetY = -10;
                    break;
                case 'lb':
                    $offsetX = 0;
                    $offsetY = $dst_info[1]-$src_info[1];
                    break;
                case 'rb':
                    $offsetX = $dst_info[0]-$src_info[0] - 10;
                    $offsetY = $dst_info[1]-$src_info[1];
                    break;
                case 'cc':
                    $offsetX = ($dst_info[0]-$src_info[0]) * 0.5;
                    $offsetY = ($dst_info[1]-$src_info[1]) * 0.5;
                    break;
            }
            if($alpha && (int)$alpha <= 100){
                //imagecopymerge($dst_im,$src_im,$offsetX,$offsetY,0,0,$src_info[0],$src_info[1], 100 - (int)$alpha);
                $this->imagecopymerge_alpha($dst_im,$src_im,$offsetX,$offsetY,0,0,$src_info[0],$src_info[1], 100 - (int)$alpha);
            }else{
                imagecopy($dst_im,$src_im,$offsetX,$offsetY,0,0,$src_info[0],$src_info[1]);
            }             
            
            switch ($dst_info[2]){ 
            case 1: 
                imagegif($dst_im, $image);
                break; 
            case 2: 
                imagejpeg($dst_im, $image);
                break; 
            case 3: 
                imagepng($dst_im, $image);
                break; 
            case 6: 
                imagewbmp($dst_im, $image);
                break;
            } 
            imagedestroy($dst_im); 
            imagedestroy($src_im); 
        }
    }
    protected function waterFont($img, $water_font, $font_src, $font_color, $water_pos = 'lt', $alpha, $font_size = 20){
        $img_size = $this->getImgSize($img);
        $image_width = $img_size['width'];
        $image_height = $img_size['height'];
        if($image_width >= 300 || $image_height >= 300){
            $dst = imagecreatefromstring(file_get_contents($img));
            $font = $font_src;
            $color_arr = explode(',', $font_color);
            $color_r = $color_arr[0];
            $color_g = $color_arr[1];
            $color_b = $color_arr[2];
            if((int)$alpha <= 100){
                $in_alpha = (int)$alpha;
            }else{
                $in_alpha = 0;
            }
            $black = imagecolorallocatealpha($dst, (int)$color_r, (int)$color_g, (int)$color_b, $in_alpha);//字体颜色
            $waterSize = $this->getWaterSize($water_font, $font_size, $font);
            $pos_x = 0;
            $pos_y = 0;
            switch($water_pos){
                case 'lt':
                    $pos_x = 0;
                    $pos_y = $waterSize['font_height'];
                    break;
                case 'rt':
                    $pos_x = $image_width - $waterSize['font_width'];
                    $pos_y = $waterSize['font_height'];
                    break;
                case 'lb':
                    $pos_x = 0;
                    $pos_y = $image_height - 10;
                    break;
                case 'rb':
                    $pos_x = $image_width - $waterSize['font_width'];
                    $pos_y = $image_height - 10;
                    break;
                case 'cc':
                    $pos_x = ($image_width - $waterSize['font_width']) * 0.5;
                    $pos_y = ($image_height - $waterSize['font_height']) * 0.5 + 10;
                    break;
            }
            imagefttext($dst, $font_size, 0, $pos_x, $pos_y, $black, $font, $water_font);
            //output image
            list($dst_w, $dst_h, $dst_type) = getimagesize($img);
            switch ($dst_type) {
                case 1://GIF
                    header('Content-Type: image/gif');
                    imagegif($dst, $img);
                    break;
                case 2://JPG
                    header('Content-Type: image/jpeg');
                    imagejpeg($dst, $img);           
                    break;
                case 3://PNG
                    header('Content-Type: image/png');
                    imagepng($dst, $img);
                    break;
                default:
                    break;
            }
            imagedestroy($dst);
        }
    }
    protected function getImgSize($img){
        $img_info = getimagesize($img);
        $size_str = $img_info[3];
        preg_match_all('/\d+/', $size_str, $matches);
        return ['width' => $matches[0][0], 'height' => $matches[0][1]];
    }
    protected function getWaterSize($text, $fontSize, $font){
        $font_size = imagettfbbox($fontSize, 0, $font, $text);
        return ['font_width' => $font_size[4] - $font_size[6], 'font_height' => $font_size[3] - $font_size[5]];
    }
    protected function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){  
        $opacity=$pct;  
        $w = imagesx($src_im);  
        $h = imagesy($src_im);  
        $cut = imagecreatetruecolor($src_w, $src_h);  
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);   
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);  
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);  
    } 
}
