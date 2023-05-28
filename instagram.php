<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Data\Data;
use Grav\Common\Page\Page;
use Grav\Common\GPM\Response;

class InstagramPlugin extends Plugin
{
    private $template_html = 'partials/instagram.html.twig';
    private $template_vars = [];

    /**
     * Return a list of subscribed events.
     *
     * @return array    The list of events of the plugin of the form
     *                      'name' => ['method_name', priority].
     */
    public static function getSubscribedEvents() {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    /**
     * Initialize configuration.
     */
    public function onPluginsInitialized()
    {
        $this->enable([
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onTwigInitialized' => ['onTwigInitialized', 0]
        ]);
    }

    /**
     * Add Twig Extensions.
     */
    public function onTwigInitialized()
    {
        $this->grav['twig']->twig->addFunction(new \Twig_SimpleFunction('instagram_feed', [$this, 'getFeed']));
    }

    /**
     * Add current directory to twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * @return array
     */
    public function getFeed($params = [])
    {
        /** @var Page $page */
        $page = $this->grav['page'];
        /** @var Twig $twig */
        $twig = $this->grav['twig'];
        /** @var Data $config */
        $config = $this->mergeConfig($page, TRUE);

        // Autoload composer components
        require __DIR__ . '/vendor/autoload.php';

        // Set access token
        $access_token = $config->get('feed_parameters.access_token');
        
        // Set number of items to retrieve
        $count = $config->get('feed_parameters.count');

        // Set fields to retrieve
        $fields = "id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username";
        
        // Instagram API url
        $url = "https://graph.instagram.com/me/media?fields={$fields}&access_token={$access_token}&limit={$count}";
        
        // Get the results from the live API
        $response = @file_get_contents($url);
        $results = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);

        $feed = [];
        foreach($results["data"] as $post){
            $item = [];
            
            $item['link'] = $post['permalink'];
            $item['image'] = $post['media_url'];
            $item['thumbnail'] = $post['thumbnail_url'];
            $item['username'] = $post['username'];
            $item['caption'] = $post['caption'];
            $item['type'] = $post['media_type'];
            $item['timestamp'] = $post['timestamp'];
            
            $feed[] = $item;
        }

        $this->template_vars = [
            'feed'      => $feed,
            'count'     => $config->get('feed_parameters.count'),
            'params'    => $params
        ];

        $output = $this->grav['twig']->twig()->render($this->template_html, $this->template_vars);

        return $output;
    }
}
