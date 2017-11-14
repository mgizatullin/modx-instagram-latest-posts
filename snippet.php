<?php

/*
 *
 * @author Igor Sukhinin <suhinin@gmail.com>, Baltic Design Colors Ltd
 * @license: https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version: 1.5.0
 *
 * Variables
 * ---------
 * @var modX $modx
 *
 * Properties
 * ----------
 * @property    string      $accountName    Instagram account name; required
 * @property    integer     $limit          Limit on the maximum number of items that will be displayed
 * @property    integer     $showVideo      Show the video if it's available (options: 1 | 0)
 * @property    string      $innerTpl       Inner chunk name
 * @property    string      $outerTpl       Outer chunk name
 * @property    string      $errorTpl       Error chunk name
 * @property    integer     $cacheEnabled   Enable/disable cache (options: 1 | 0)
 * @property    integer     $cacheExpTime   Cache expiry time
 *
 */


class InstagramLatestPosts
{
    protected $accountName;
    protected $limit;
    protected $showVideo;
    protected $innerTpl;
    protected $outerTpl;
    protected $errorTpl;
    protected $cacheEnabled;
    protected $cacheExpTime;
    protected $modx;
    protected $serverMethod;
    protected $accountUrl;
    protected $output;
    protected $cacheKey;
    protected $cacheDir;
    protected $error;
    protected $fullName;
    protected $id;
    protected $profile_picture;

    /**
     * InstagramLatestPosts constructor.
     *
     * @param array $config Array of properties
     */
    public function __construct($config = [])
    {
        // Initialize the essential properties
        $this->modx         = $config['modx'];
        $this->accountName  = $config['accountName'];
        $this->limit        = $config['limit'];
        $this->showVideo    = $config['showVideo'];
        $this->innerTpl     = $config['innerTpl'];
        $this->outerTpl     = $config['outerTpl'];
        $this->errorTpl     = $config['errorTpl'];
        $this->cacheEnabled = $config['cacheEnabled'];
        $this->cacheExpTime = $config['cacheExpTime'];
        $this->cacheKey     = 'latest_posts';
        $this->cacheDir     = 'instagram_latest_posts';
    }

    /**
     * Runs the data processing
     *
     * @return boolean $result The result of data processing
     */
    public function run()
    {
        // Check if cache is enabled
        if ($this->cacheEnabled == 1) {
            // Try to get the cache
            $cache = $this->getCache();

            // Check if cache is available and is not expired
            if ($cache !== null) {
                // Set the output from the cache
                $this->output = $cache;

                // Stop processing the snippet
                return true;
            }
        }

        // Check if the Instagram account name is not set
        if ($this->accountName == '') {
            $this->error = 'Instagram account name is required. Please set this property in your snippet call.';
            return false;
        }

        // Get the available server method to download the remote content
        $this->serverMethod = $this->getServerMethod();

        // Check if no server method is available
        if ($this->serverMethod === null) {
            $this->error = 'Please enable allow_url_fopen or cURL on your web server.';
            return false;
        }

        // Set the account URL
        $this->accountUrl   = 'https://www.instagram.com/' . $this->accountName;

        // Set the JSON URL
        $jsonUrl = $this->accountUrl . '/?__a=1';

        // Get the JSON content
        $json = $this->getJsonContent($jsonUrl);

        // Check if loading of JSON content failed
        if ($json === false) {
            $this->error = 'The remote loading of JSON content failed. Please check if your account name is correct.';
            return false;
        }

        // Parse JSON in an object
        $data = $this->parseJson($json);

        // Check if JSON parsing failed
        if ($data === null || !isset($data->user->media)) {
            $this->error = 'The JSON parsing failed.';
            return false;
        }

        // Set full name
        $this->fullName = (isset($data->user->full_name)) ? $data->user->full_name : '';

        // Set account ID
        $this->id = (isset($data->user->id)) ? $data->user->id : '';

        // Set profile picture
        $this->profile_picture = (isset($data->user->profile_pic_url_hd)) ? $data->user->profile_pic_url_hd : '';

        // Get JSON data in an object containing resources
        $resources = $this->getResources($data->user->media);

        // Check if there is no any resource
        if (count($resources) == 0) {
            $this->error = 'There are no posts yet in the profile: ' . $this->accountName;
            return false;
        }

        // Set the output
        $this->output = $this->setOutput($resources);

        // Check if cache is enabled
        if ($this->cacheEnabled == 1) {
            // Save a new cache
            $this->setCache();
        }

        return true;
    }

    /**
     * Gets the output
     *
     * @return string $output HTML output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Gets the error (if there was any)
     *
     * @return string Error content
     */
    public function getError()
    {
        // Check if there were no any error
        if (!isset($this->error)) {
            return '';
        }

        // Get the error content
        return $this->modx->getChunk($this->errorTpl, ['error' => $this->error]);
    }


    /**
     * Gets the available server method which allows to download the remote content
     *
     * @return mixed string | null $serverMethod The name of server method or null if both allow_url_fopen and cURL are disabled
     */
    protected function getServerMethod()
    {
        // Check if file_get_contents is enabled
        if (ini_get('allow_url_fopen')) {
            $serverMethod = 'fopen';
        } // Check if cURL is enabled
        elseif (function_exists('curl_version')) {
            $serverMethod = 'curl';
        }

        return (isset($serverMethod)) ? $serverMethod : null;
    }

    /**
     * Gets the JSON content
     *
     * @param string $url JSON url
     * @return mixed string | false $json JSON content or false if there was some error while downloading the remote content
     */
    protected function getJsonContent($url = '')
    {
        $json = null;

        // Check if file_get_contents is enabled
        if ($this->serverMethod == 'fopen') {
            // Get the JSON content using fopen
            $json = $this->loadFileOpen($url);
        } // Check if cURL is enabled
        elseif ($this->serverMethod == 'curl') {
            // Try to get the JSON content using cURL
            $json = $this->loadCurl($url);
        }

        return $json;
    }

    /**
     * Parses the JSON string in an object
     *
     * @param string $json JSON content
     * @return mixed stdClass | null $data Object containing JSON data or null if the JSON processing failed
     */
    protected function parseJson($json = '')
    {
        // Decode JSON an object
        return @json_decode($json);
    }

    /**
     * Loads the remote content using file_get_contents()
     *
     * @param string $url
     * @return mixed string | boolean
     */
    protected function loadFileOpen($url = '')
    {
        return file_get_contents($url);
    }

    /**
     * Loads the remote content using Client URL Library (cURL)
     *
     * @param string $url
     * @return mixed
     */
    protected function loadCurl($url = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }

    /**
     * Gets the external video source
     *
     * @param string $pageId Page ID
     * @return string $resources Prepared array of resources
     */
    protected function getVideoSource($pageId)
    {
        $jsonUrl = 'https://www.instagram.com/p/' . $pageId . '/?__a=1';

        // Get the JSON content
        $json = $this->getJsonContent($jsonUrl);

        // Check if loading of JSON content failed
        if ($json === false) {
            return '';
        }

        // Parse JSON in an object
        $data = $this->parseJson($json);

        // Check if JSON parsing failed
        if ($data === null || !isset($data->graphql->shortcode_media->video_url)) {
            return '';
        }

        return $data->graphql->shortcode_media->video_url;
    }

    /**
     * Gets the resources
     *
     * @param stdClass $data Object containing JSON data
     * @return array $resources Prepared array of resources
     */
    protected function getResources(stdClass $data)
    {
        // Prepare the variables
        $resources = [];
        $i = 0;

        foreach ($data->nodes as $node) {
            // Check if we reached the limit of items
            if ($i == $this->limit) {
                // Stop the execution of this loop as we have already reached the limit
                break;
            }

            // Check if video is available and if it should be shown
            if (isset($node->is_video) && $this->showVideo) {
                $resources[$i]['url'] = $this->getVideoSource($node->code);
                $resources[$i]['type'] = 'video';
            } else {
                // Otherwise set the image preview
                $resources[$i]['url'] = $node->thumbnail_src;
                $resources[$i]['type'] = 'image';
            }
            
            // Set the caption of the post
            $resources[$i]['caption'] = $node->caption;

            // Set the link to the corresponding post
            $resources[$i]['link'] = 'https://www.instagram.com/p/' . $node->code . '/';

            // Set full name
            $resources[$i]['user']['full_name'] = $this->fullName;

            // Set account ID
            $resources[$i]['user']['id'] = $this->id;

            // Set profile picture
            $resources[$i]['user']['profile_picture'] = $this->profile_picture;

            // Set username
            $resources[$i]['user']['username'] = $this->accountName;

            $i++;
        }

        return $resources;
    }

    /**
     * Sets the output
     *
     * @param array $resources
     * @return string $output
     */
    protected function setOutput($resources = [])
    {
        $items = '';

        foreach ($resources as $resource) {
            // Get the inner content
            $items .= $this->modx->getChunk($this->innerTpl, $resource);
        }

        // Get the outer content
        $output = $this->modx->getChunk($this->outerTpl, [
            'accountUrl'    => $this->accountUrl,
            'items'         => $items,
        ]);

        return $output;
    }

    /**
     * Gets the cache if it's available
     *
     * @return mixed string | null $data The cached data or null if the cache is expired / not available yet
     */
    protected function getCache()
    {
        // Get the cache using MODX Cache Manager
        $data = $this->modx->cacheManager->get(
            $this->cacheKey,
            [
                xPDO::OPT_CACHE_KEY => $this->cacheDir,
            ]
        );

        return $data;
    }

    /**
     * Saves the output in MODX custom cache
     */
    protected function setCache()
    {
        // Save the cache using MODX Cache Manager
        $this->modx->cacheManager->set(
            $this->cacheKey,
            $this->output,
            $this->cacheExpTime,
            [
                xPDO::OPT_CACHE_KEY => $this->cacheDir,
            ]
        );
    }

}

// Create config array
$config = [
    'modx'          => $modx,
    'accountName'   => $modx->getOption('accountName', $scriptProperties, '', true),
    'limit'         => $modx->getOption('limit', $scriptProperties, 6, true),
    'showVideo'     => $modx->getOption('showVideo', $scriptProperties, 0, true),
    'innerTpl'      => $modx->getOption('innerTpl', $scriptProperties, 'Instagram-Inner', true),
    'outerTpl'      => $modx->getOption('outerTpl', $scriptProperties, 'Instagram-Outer', true),
    'errorTpl'      => $modx->getOption('errorTpl', $scriptProperties, 'Instagram-Error', true),
    'cacheEnabled'  => $modx->getOption('cacheEnabled', $scriptProperties, 1, true),
    'cacheExpTime'  => $modx->getOption('cacheExpTime', $scriptProperties, 3600, true),
];

// Create a new InstagramLatestPosts class instance
$inst = new InstagramLatestPosts($config);

// Run the data processing
if ($inst->run()) {
    // Get the output if processing was successfull
    $output = $inst->getOutput();
} else {
    // Get the error explaining the issue
    $output = $inst->getError();
}

return $output;
