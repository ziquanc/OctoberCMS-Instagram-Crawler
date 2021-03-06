<?php

namespace InstagramScraper\Model;

use InstagramScraper\Endpoints;

/**
 * Class Media
 * @package InstagramScraper\Model
 */
class Media extends AbstractModel
{
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_SIDECAR = 'sidecar';
    const TYPE_CAROUSEL = 'carousel';

    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var string
     */
    protected $shortCode = '';

    /**
     * @var int
     */
    protected $createdTime = 0;

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var string
     */
    protected $link = '';

    /**
     * @var string
     */
    protected $imageLowResolutionUrl = '';

    /**
     * @var string
     */
    protected $imageThumbnailUrl = '';

    /**
     * @var string
     */
    protected $imageStandardResolutionUrl = '';

    /**
     * @var string
     */
    protected $imageHighResolutionUrl = '';

    /**
     * @var array
     */
    protected $carouselMedia = [];

    /**
     * @var string
     */
    protected $caption = '';

    /**
     * @var bool
     */
    protected $isCaptionEdited = false;

    /**
     * @var bool
     */
    protected $isAd = false;

    /**
     * @var string
     */
    protected $videoLowResolutionUrl = '';

    /**
     * @var string
     */
    protected $videoStandardResolutionUrl = '';

    /**
     * @var string
     */
    protected $videoLowBandwidthUrl = '';

    /**
     * @var int
     */
    protected $videoViews = 0;

    /**
     * @var Account
     */
    protected $owner;

    /**
     * @var int
     */
    protected $ownerId = 0;

    /**
     * @var int
     */
    protected $likesCount = 0;

    /**
     * @var
     */
    protected $locationId;

    /**
     * @var string
     */
    protected $locationName = '';

    /**
     * @var string
     */
    protected $commentsCount = 0;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getShortCode()
    {
        return $this->shortCode;
    }

    /**
     * @return int
     */
    public function getCreatedTime()
    {
        return $this->createdTime;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getImageLowResolutionUrl()
    {
        return $this->imageLowResolutionUrl;
    }

    /**
     * @return string
     */
    public function getImageThumbnailUrl()
    {
        return $this->imageThumbnailUrl;
    }

    /**
     * @return string
     */
    public function getImageStandardResolutionUrl()
    {
        return $this->imageStandardResolutionUrl;
    }

    /**
     * @return string
     */
    public function getImageHighResolutionUrl()
    {
        return $this->imageHighResolutionUrl;
    }

    /**
     * @return array
     */
    public function getCarouselMedia()
    {
        return $this->carouselMedia;
    }

    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @return bool
     */
    public function isCaptionEdited()
    {
        return $this->isCaptionEdited;
    }

    /**
     * @return bool
     */
    public function isAd()
    {
        return $this->isAd;
    }

    /**
     * @return string
     */
    public function getVideoLowResolutionUrl()
    {
        return $this->videoLowResolutionUrl;
    }

    /**
     * @return string
     */
    public function getVideoStandardResolutionUrl()
    {
        return $this->videoStandardResolutionUrl;
    }

    /**
     * @return string
     */
    public function getVideoLowBandwidthUrl()
    {
        return $this->videoLowBandwidthUrl;
    }

    /**
     * @return int
     */
    public function getVideoViews()
    {
        return $this->videoViews;
    }

    /**
     * @return Account
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * @return int
     */
    public function getLikesCount()
    {
        return $this->likesCount;
    }

    /**
     * @return mixed
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @return string
     */
    public function getLocationName()
    {
        return $this->locationName;
    }

    /**
     * @return string
     */
    public function getCommentsCount()
    {
        return $this->commentsCount;
    }

    /**
     * @param string $code
     *
     * @return int
     */
    public static function getIdFromCode($code)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $id = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $c = $code[$i];
            $id = $id * 64 + strpos($alphabet, $c);
        }
        return $id;
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getLinkFromId($id)
    {
        $code = Media::getCodeFromId($id);
        return Endpoints::getMediaPageLink($code);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public static function getCodeFromId($id)
    {
        $parts = explode('_', $id);
        $id = $parts[0];
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $code = '';
        while ($id > 0) {
            $remainder = $id % 64;
            $id = ($id - $remainder) / 64;
            $code = $alphabet{$remainder} . $code;
        };
        return $code;
    }

    /**
     * @param $value
     * @param $prop
     */
    protected function initPropertiesCustom($value, $prop, $arr)
    {
        switch ($prop) {
            case 'id':
                $this->id = $value;
                break;
            case 'type':
                $this->type = $value;
                break;
            case 'created_time':
                $this->createdTime = (int) $value;
                break;
            case 'code':
                $this->shortCode = $value;
                break;
            case 'link':
                $this->link = $value;
                break;
            case 'comments':
                $this->commentsCount = $arr[$prop]['count'];
                break;
            case 'likes':
                $this->likesCount = $arr[$prop]['count'];
                break;
            case 'images':
                $images = self::getImageUrls($arr[$prop]['standard_resolution']['url']);
                $this->imageLowResolutionUrl = $images['low'];
                $this->imageThumbnailUrl = $images['thumbnail'];
                $this->imageStandardResolutionUrl = $images['standard'];
                $this->imageHighResolutionUrl = $images['high'];
                break;
            case 'carousel_media':
                $this->type = self::TYPE_CAROUSEL;
                $this->carouselMedia = [];
                foreach ($arr["carousel_media"] as $carouselArray) {
                    self::setCarouselMedia($arr, $carouselArray, $this);
                }
                break;
            case 'caption':
                if(isset($arr[$prop]['text'])){
                $this->caption = $arr[$prop]['text'];}
                break;
            case 'video_views':
                $this->videoViews = $value;
                break;
            case 'videos':
                $this->videoLowResolutionUrl = $arr[$prop]['low_resolution']['url'];
                $this->videoStandardResolutionUrl = $arr[$prop]['standard_resolution']['url'];
                $this->videoLowBandwidthUrl = $arr[$prop]['low_bandwidth']['url'];
                break;
            case 'location':
                switch ($prop) {
                    case 'id':
                        $this->locationId = $value[$prop];
                        break;
                    case 'name':
                        $this->locationId = $value[$prop];
                        break;
                }
                $this->locationName = $arr[$prop]['name'];
                break;
            case 'user':
                $this->owner = Account::create($arr[$prop]);
                break;
            case 'is_video':
                $this->type = self::TYPE_VIDEO;
                break;
            case 'video_url':
                $this->videoStandardResolutionUrl = $value;
                break;
            case 'video_view_count':
                $this->videoViews = $value;
                break;
            case 'caption_is_edited':
                $this->isCaptionEdited =$value;
                break;
            case 'is_ad':
                $this->isAd = $value;
                break;
            case 'taken_at_timestamp':
                $this->createdTime = $value;
                break;
            case 'shortcode':
                $this->shortCode = $value;
                $this->link = Endpoints::getMediaPageLink($this->shortCode);
                break;
            case 'edge_media_to_comment':
                $this->commentsCount = $arr[$prop]['count'];
                $this->likesCount = $arr[$prop]['count'];
                break;
            case 'display_url':
                $images = self::getImageUrls($arr[$prop]);
                $this->imageStandardResolutionUrl = $images['standard'];
                $this->imageLowResolutionUrl = $images['low'];
                $this->imageHighResolutionUrl = $images['high'];
                $this->imageThumbnailUrl = $images['thumbnail'];
                break;
            case 'edge_media_to_caption':
                $this->caption = $arr[$prop]['edges'][0]['node']['text'];
                break;
            case 'owner':
                $this->owner = Account::create($arr[$prop]);
                break;
            case 'date':
                $this->createdTime = (int) $value;
                break;
            case 'display_src':
                $images = self::getImageUrls($value);
                $this->imageStandardResolutionUrl = $images['standard'];
                $this->imageLowResolutionUrl = $images['low'];
                $this->imageHighResolutionUrl = $images['high'];
                $this->imageThumbnailUrl = $images['thumbnail'];
                $this->type = self::TYPE_IMAGE;
                break;
        }
        if (!$this->ownerId && !is_null($this->owner)) {
            $this->ownerId = $this->getOwner()->getId();
        }
    }

    /**
     * @param string $imageUrl
     *
     * @return array
     */
    private static function getImageUrls($imageUrl)
    {
        $parts = explode('/', parse_url($imageUrl)['path']);
        $imageName = $parts[sizeof($parts) - 1];
        $urls = [
            'thumbnail' => Endpoints::INSTAGRAM_CDN_URL . 't/s150x150/' . $imageName,
            'low'       => Endpoints::INSTAGRAM_CDN_URL . 't/s320x320/' . $imageName,
            'standard'  => Endpoints::INSTAGRAM_CDN_URL . 't/s640x640/' . $imageName,
            'high'      => Endpoints::INSTAGRAM_CDN_URL . 't/' . $imageName,
        ];
        return $urls;
    }

    /**
     * @param $mediaArray
     * @param $carouselArray
     * @param $instance
     *
     * @return mixed
     */
    private static function setCarouselMedia($mediaArray, $carouselArray, $instance)
    {
        $carouselMedia = new CarouselMedia();
        $carouselMedia->setType($carouselArray['type']);

        if (isset($carouselArray['images'])) {
            $carouselImages = self::getImageUrls($carouselArray['images']['standard_resolution']['url']);
            $carouselMedia->setImageLowResolutionUrl($carouselImages['low']);
            $carouselMedia->setImageThumbnailUrl($carouselImages['thumbnail']);
            $carouselMedia->setImageStandardResolutionUrl($carouselImages['standard']);
            $carouselMedia->setImageHighResolutionUrl($carouselImages['high']);
        }

        if ($carouselMedia->getType() === self::TYPE_VIDEO) {
            if (isset($mediaArray['video_views'])) {
                $carouselMedia->setVideoViews($carouselArray['video_views']);
            }
            if (isset($carouselArray['videos'])) {
                $carouselMedia->setVideoLowResolutionUrl($carouselArray['videos']['low_resolution']['url']);
                $carouselMedia->setVideoStandardResolutionUrl($carouselArray['videos']['standard_resolution']['url']);
                $carouselMedia->setVideoLowBandwidthUrl($carouselArray['videos']['low_bandwidth']['url']);
            }
        }
        array_push($instance->carouselMedia, $carouselMedia);
        return $mediaArray;
    }
}
