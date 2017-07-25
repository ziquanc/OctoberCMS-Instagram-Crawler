<?php namespace Kent\InstagramCrawler\Components;

use Kent\InstagramCrawler\autoload;
use Cms\Classes\ComponentBase;
use Kent\InstagramCrawler\Models\Settings;
use InstagramScraper\Instagram;

class UserFeed extends ComponentBase
{
    public $media;

    public function componentDetails()
    {
        return [
            'name'        => 'User Feed',
            'description' => 'Instagram media based on a specified user.'
        ];
    }

    public function defineProperties()
    {
        return [
            'user_name' => [
                'title'             => 'User Name',
                'description'       => 'Restrict returned media by the specified user.',
                'default'           => '',
                'type'              => 'string',
                'validationPattern' => '^(?=\s*\S).*$',
                'validationMessage' => 'The User Name property is required'
            ],
            'limit' => [
                'title'             => 'Limit',
                'description'       => 'The number of media to be displayed (20 maximum).',
                'default'           => 10,
                'type'              => 'string',
                'validationPattern' => '^[0-9]*$',
                'validationMessage' => 'The Limit property should be numeric'
            ],
            'cache' => [
                'title'             => 'Cache',
                'description'       => 'The number of minutes to cache the media.',
                'default'           => 10,
                'type'              => 'string',
                'validationPattern' => '^[0-9]*$',
                'validationMessage' => 'The Cache property should be numeric'
            ]
        ];
    }

    public function onRun()
    {
      $api = new Instagram();
      $this->media = $this->page['media'] = $api->getMedias($this->property('user_name'),  (int)$this->property('limit'));
    }
}
