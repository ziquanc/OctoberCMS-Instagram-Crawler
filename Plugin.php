<?php namespace Kent\InstagramCrawler;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{

    public function pluginDetails()
    {
        return [
            'name'        => 'Instagram Crawler',
            'description' => 'Provides integration with Instagram without Authorization.',
            'author'      => 'Kent',
            'icon'        => 'icon-instagram'
        ];
    }
    public function registerComponents()
    {
      return [
        'Kent\InstagramCrawler\Components\TagFeed' => 'TagFeed',
        'Kent\InstagramCrawler\Components\UserFeed' => 'UserFeed'
      ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'Instagram Crawler',
                'icon'        => 'icon-instagram',
                'description' => 'Configure Instagram username or tag parameters.',
                'class'       => 'Kent\InstagramCrawler\Models\Settings',
                'order'       => 210
            ]
        ];
    }
}
