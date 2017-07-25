<?php

namespace InstagramScraper;

use InstagramScraper\Exception\InstagramAuthException;
use InstagramScraper\Exception\InstagramException;
use InstagramScraper\Exception\InstagramNotFoundException;
use InstagramScraper\Model\Account;
use InstagramScraper\Model\Comment;
use InstagramScraper\Model\Location;
use InstagramScraper\Model\Media;
use InstagramScraper\Model\Tag;
use phpFastCache\CacheManager;
use Unirest\Request;

class Instagram
{
    const MAX_COMMENTS_PER_REQUEST = 300;
    private static $instanceCache;
    public $sessionUsername;
    public $sessionPassword;
    public $userSession;

    /**
     * @param string $username
     * @param string $password
     * @param null   $sessionFolder
     *
     * @return Instagram
     */
    public static function withCredentials($username, $password, $sessionFolder = null)
    {
        if (is_null($sessionFolder)) {
            $sessionFolder = __DIR__ . DIRECTORY_SEPARATOR . 'sessions' . DIRECTORY_SEPARATOR;
        }
        if (is_string($sessionFolder)) {
            CacheManager::setDefaultConfig([
                'path' => $sessionFolder,
            ]);
            self::$instanceCache = CacheManager::getInstance('files');
        } else {
            self::$instanceCache = $sessionFolder;
        }
        $instance = new self();
        $instance->sessionUsername = $username;
        $instance->sessionPassword = $password;
        return $instance;
    }

    /**
     * @param string $username
     *
     * @return Account
     * @throws InstagramException
     * @throws InstagramNotFoundException
     */
    public static function getAccount($username)
    {

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getAccountJsonLink($username));
        if ($response->code === 404) {
            throw new InstagramNotFoundException('Account with given username does not exist.');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }

        $userArray = json_decode($response->raw_body, true);
        if (!isset($userArray['user'])) {
            throw new InstagramException('Account with this username does not exist');
        }
        return Account::create($userArray['user']);
    }

    /**
     * @param string $username
     * @param int    $count
     * @param string $maxId
     *
     * @return array
     * @throws InstagramException
     */
    public static function getMedias($username, $count = 20, $maxId = '')
    {
        $index = 0;
        $medias = [];
        $isMoreAvailable = true;
        while ($index < $count && $isMoreAvailable) {

            Request::verifyPeer(false);
            $response = Request::get(Endpoints::getAccountMediasJsonLink($username, $maxId));
            if ($response->code !== 200) {
                throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
            }

            $arr = json_decode($response->raw_body, true);
            if (!is_array($arr)) {
                throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
            }
            // fix - count takes longer/has more overhead
            if (empty($arr['items']) || !isset($arr['items'])) {
                return [];
            }
            foreach ($arr['items'] as $mediaArray) {
                if ($index === $count) {
                    return $medias;
                }
                $medias[] = Media::create($mediaArray);
                $index++;
            }
            if (empty($arr['items']) || !isset($arr['items'])) {
                return $medias;
            }
            $maxId = $arr['items'][count($arr['items']) - 1]['id'];
            $isMoreAvailable = $arr['more_available'];
        }
        return $medias;
    }

    /**
     * @param string $username
     *
     * @return array
     * @throws InstagramException
     * @throws InstagramNotFoundException
     */
    public static function searchAccountsByUsername($username)
    {
        // TODO: Add tests and auth

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getGeneralSearchJsonLink($username));
        if ($response->code === 404) {
            throw new InstagramNotFoundException('Account with given username does not exist.');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }

        $jsonResponse = json_decode($response->raw_body, true);
        if (!isset($jsonResponse['status']) || $jsonResponse['status'] != 'ok') {
            throw new InstagramException('Response code is not equal 200. Something went wrong. Please report issue.');
        }
        if (!isset($jsonResponse['users']) || count($jsonResponse['users']) == 0) {
            return [];
        }

        $accounts = [];
        foreach ($jsonResponse['users'] as $jsonAccount) {
            $accounts[] = Account::create($jsonAccount['user']);
        }
        return $accounts;
    }

    /**
     * @param string $tag
     *
     * @return array
     * @throws InstagramException
     * @throws InstagramNotFoundException
     */
    public static function searchTagsByTagName($tag)
    {
        // TODO: Add tests and auth

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getGeneralSearchJsonLink($tag));
        if ($response->code === 404) {
            throw new InstagramNotFoundException('Account with given username does not exist.');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }

        $jsonResponse = json_decode($response->raw_body, true);
        if (!isset($jsonResponse['status']) || $jsonResponse['status'] != 'ok') {
            throw new InstagramException('Response code is not equal 200. Something went wrong. Please report issue.');
        }

        if (!isset($jsonResponse['hashtags']) || count($jsonResponse['hashtags']) == 0) {
            return [];
        }
        $hashtags = [];
        foreach ($jsonResponse['hashtags'] as $jsonHashtag) {
            $hashtags[] = Tag::create($jsonHashtag['hashtag']);
        }
        return $hashtags;
    }

    /**
     * @param $mediaId
     *
     * @return Media
     */
    public function getMediaById($mediaId)
    {
        $mediaLink = Media::getLinkFromId($mediaId);
        return self::getMediaByUrl($mediaLink);
    }

    /**
     * @param string $mediaUrl
     *
     * @return Media
     * @throws InstagramException
     * @throws InstagramNotFoundException
     */
    public function getMediaByUrl($mediaUrl)
    {
        if (filter_var($mediaUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Malformed media url');
        }
        Request::verifyPeer(false);
        $response = Request::get(rtrim($mediaUrl, '/') . '/?__a=1', $this->generateHeaders($this->userSession));
        if ($response->code === 404) {
            throw new InstagramNotFoundException('Media with given code does not exist or account is private.');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }
        $mediaArray = json_decode($response->raw_body, true);
        if (!isset($mediaArray['graphql']['shortcode_media'])) {
            throw new InstagramException('Media with this code does not exist');
        }
        return Media::create($mediaArray['graphql']['shortcode_media']);
    }
    /**
     * @param string $username
     * @param string $maxId
     *
     * @return array
     * @throws InstagramException
     */
    public function getPaginateMedias($username, $maxId = '')
    {
        $hasNextPage = true;
        $medias = [];

        $toReturn = [
            'medias'      => $medias,
            'maxId'       => $maxId,
            'hasNextPage' => $hasNextPage,
        ];

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getAccountMediasJsonLink($username, $maxId),
            $this->generateHeaders($this->userSession));

        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }

        $arr = json_decode($response->raw_body, true);

        if (!is_array($arr)) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }

        if (count($arr['items']) === 0) {
            return $toReturn;
        }

        foreach ($arr['items'] as $mediaArray) {
            $medias[] = Media::create($mediaArray);
        }

        $maxId = $arr['items'][count($arr['items']) - 1]['id'];
        $hasNextPage = $arr['more_available'];

        $toReturn = [
            'medias'      => $medias,
            'maxId'       => $maxId,
            'hasNextPage' => $hasNextPage,
        ];

        return $toReturn;
    }

    /**
     * @param      $mediaId
     * @param int  $count
     * @param null $maxId
     *
     * @return array
     */
    public function getMediaCommentsById($mediaId, $count = 10, $maxId = null)
    {
        $code = Media::getCodeFromId($mediaId);
        return self::getMediaCommentsByCode($code, $count, $maxId);
    }

    /**
     * @param      $code
     * @param int  $count
     * @param null $maxId
     *
     * @return array
     * @throws InstagramException
     */
    public function getMediaCommentsByCode($code, $count = 10, $maxId = null)
    {
        $remain = $count;
        $comments = [];
        $index = 0;
        $hasPrevious = true;
        while ($hasPrevious && $index < $count) {
            if ($remain > self::MAX_COMMENTS_PER_REQUEST) {
                $numberOfCommentsToRetreive = self::MAX_COMMENTS_PER_REQUEST;
                $remain -= self::MAX_COMMENTS_PER_REQUEST;
                $index += self::MAX_COMMENTS_PER_REQUEST;
            } else {
                $numberOfCommentsToRetreive = $remain;
                $index += $remain;
                $remain = 0;
            }
            if (!isset($maxId)) {
                $maxId = '';

            }
            $commentsUrl = Endpoints::getCommentsBeforeCommentIdByCode($code, $numberOfCommentsToRetreive, $maxId);

            Request::verifyPeer(false);
            $response = Request::get($commentsUrl, $this->generateHeaders($this->userSession));
            if ($response->code !== 200) {
                throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
            }
            $cookies = self::parseCookies($response->headers['Set-Cookie']);
            $this->userSession['csrftoken'] = $cookies['csrftoken'];
            $jsonResponse = json_decode($response->raw_body, true);
            $nodes = $jsonResponse['data']['shortcode_media']['edge_media_to_comment']['edges'];
            foreach ($nodes as $commentArray) {
                $comments[] = Comment::create($commentArray['node']);
            }
            $hasPrevious = $jsonResponse['data']['shortcode_media']['edge_media_to_comment']['page_info']['has_next_page'];
            $numberOfComments = $jsonResponse['data']['shortcode_media']['edge_media_to_comment']['count'];
            if ($count > $numberOfComments) {
                $count = $numberOfComments;
            }
            if (sizeof($nodes) == 0) {
                return $comments;
            }
            $maxId = $nodes[sizeof($nodes) - 1]['node']['id'];
        }
        return $comments;
    }

    /**
     * @param string $id
     *
     * @return Account
     * @throws InstagramException
     */
    public function getAccountById($id)
    {
        // Use the follow page to get the account. The follow url will redirect to the home page for the user,
        // which has the username embedded in the url.

        if (!is_numeric($id)) {
            throw new \InvalidArgumentException('User id must be integer or integer wrapped in string');
        }

        $url = Endpoints::getFollowUrl($id);

        Request::verifyPeer(false);
        // Cut a request by disabling redirects.
        Request::curlOpt(CURLOPT_FOLLOWLOCATION, FALSE);
        $response = Request::get($url, $this->generateHeaders($this->userSession));
        Request::curlOpt(CURLOPT_FOLLOWLOCATION, TRUE);

        if($response->code === 400){
            throw new InstagramException('Account with this id does not exist.');
        }

        if ($response->code !== 302){
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->raw_body . ' Something went wrong. Please report issue.');
        }

        $cookies = self::parseCookies($response->headers['Set-Cookie']);
        $this->userSession['csrftoken'] = $cookies['csrftoken'];

        // Get the username from the response url.
        $responseUrl = $response->headers['Location'];
        $urlParts = explode('/', rtrim($responseUrl, '/'));
        $username = end($urlParts);

        return self::getAccount($username);
    }
    /**
     * @param string $tag
     * @param int    $count
     * @param string $maxId
     *
     * @return array
     * @throws InstagramException
     */
    public function getMediasByTag($tag, $count = 12, $maxId = '')
    {
        $index = 0;
        $medias = [];
        $mediaIds = [];
        $hasNextPage = true;
        while ($index < $count && $hasNextPage) {
            Request::verifyPeer(false);
            $response = Request::get(Endpoints::getMediasJsonByTagLink($tag, $maxId),
                $this->generateHeaders($this->userSession));
            if ($response->code !== 200) {
                throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
            }
            $cookies = self::parseCookies($response->headers['Set-Cookie']);
            $this->userSession['csrftoken'] = $cookies['csrftoken'];
            $arr = json_decode($response->raw_body, true);
            // return $response->raw_body;
            if (!is_array($arr)) {
                throw new InstagramException('Response decoding failed. Returned data corrupted or this library outdated. Please report issue');
            }
            if (count($arr['tag']['media']['count']) === 0) {
                return [];
            }
            $nodes = $arr['tag']['media']['nodes'];
            foreach ($nodes as $mediaArray) {
                if ($index === $count) {
                    return $medias;
                }
                $media = Media::create($mediaArray);
                if (in_array($media->getId(), $mediaIds)) {
                    return $medias;
                }
                $mediaIds[] = $media->getId();
                $medias[] = $media;
                $index++;
            }
            if (count($nodes) == 0) {
                return $medias;
            }
            $maxId = $arr['tag']['media']['page_info']['end_cursor'];
            $hasNextPage = $arr['tag']['media']['page_info']['has_next_page'];
        }
        return $medias;
    }

    /**
     * @param string $tag
     * @param string $maxId
     *
     * @return array
     * @throws InstagramException
     */
    public function getPaginateMediasByTag($tag, $maxId = '')
    {
        $hasNextPage = true;
        $medias = [];

        $toReturn = [
            'medias'      => $medias,
            'maxId'       => $maxId,
            'hasNextPage' => $hasNextPage,
        ];
        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getMediasJsonByTagLink($tag, $maxId),
            $this->generateHeaders($this->userSession));

        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }

        $cookies = self::parseCookies($response->headers['Set-Cookie']);
        $this->userSession['csrftoken'] = $cookies['csrftoken'];

        $arr = json_decode($response->raw_body, true);

        if (!is_array($arr)) {
            throw new InstagramException('Response decoding failed. Returned data corrupted or this library outdated. Please report issue');
        }

        if (count($arr['tag']['media']['count']) === 0) {
            return $toReturn;
        }

        $nodes = $arr['tag']['media']['nodes'];

        if (count($nodes) == 0) {
            return $toReturn;
        }

        foreach ($nodes as $mediaArray) {
            $medias[] = Media::create($mediaArray);
        }

        $maxId = $arr['tag']['media']['page_info']['end_cursor'];
        $hasNextPage = $arr['tag']['media']['page_info']['has_next_page'];
        $count = $arr['tag']['media']['count'];

        $toReturn = [
            'medias'      => $medias,
            'count'       => $count,
            'maxId'       => $maxId,
            'hasNextPage' => $hasNextPage,
        ];

        return $toReturn;
    }

    /**
     * @param $tagName
     *
     * @return array
     * @throws InstagramException
     * @throws InstagramNotFoundException
     */
    public function getTopMediasByTagName($tagName)
    {

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getMediasJsonByTagLink($tagName, ''),
            $this->generateHeaders($this->userSession));
        if ($response->code === 404) {
            throw new InstagramNotFoundException('Account with given username does not exist.');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }
        $cookies = self::parseCookies($response->headers['Set-Cookie']);
        $this->userSession['csrftoken'] = $cookies['csrftoken'];
        $jsonResponse = json_decode($response->raw_body, true);
        $medias = [];
        foreach ($jsonResponse['tag']['top_posts']['nodes'] as $mediaArray) {
            $medias[] = Media::create($mediaArray);
        }
        return $medias;
    }

    /**
     * @param $facebookLocationId
     *
     * @return array
     * @throws InstagramException
     * @throws InstagramNotFoundException
     */
    public function getLocationTopMediasById($facebookLocationId)
    {

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getMediasJsonByLocationIdLink($facebookLocationId),
            $this->generateHeaders($this->userSession));
        if ($response->code === 404) {
            throw new InstagramNotFoundException('Location with this id doesn\'t exist');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }
        $cookies = self::parseCookies($response->headers['Set-Cookie']);
        $this->userSession['csrftoken'] = $cookies['csrftoken'];
        $jsonResponse = json_decode($response->raw_body, true);
        $nodes = $jsonResponse['location']['top_posts']['nodes'];
        $medias = [];
        foreach ($nodes as $mediaArray) {
            $medias[] = Media::create($mediaArray);
        }
        return $medias;
    }

    /**
     * @param string $facebookLocationId
     * @param int    $quantity
     * @param string $offset
     *
     * @return array
     * @throws InstagramException
     */
    public function getLocationMediasById($facebookLocationId, $quantity = 12, $offset = '')
    {
        $index = 0;
        $medias = [];
        $hasNext = true;
        while ($index < $quantity && $hasNext) {

            Request::verifyPeer(false);
            $response = Request::get(Endpoints::getMediasJsonByLocationIdLink($facebookLocationId, $offset),
                $this->generateHeaders($this->userSession));
            if ($response->code !== 200) {
                throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
            }
            $cookies = self::parseCookies($response->headers['Set-Cookie']);
            $this->userSession['csrftoken'] = $cookies['csrftoken'];
            $arr = json_decode($response->raw_body, true);
            $nodes = $arr['location']['media']['nodes'];
            foreach ($nodes as $mediaArray) {
                if ($index === $quantity) {
                    return $medias;
                }
                $medias[] = Media::create($mediaArray);
                $index++;
            }
            if (count($nodes) == 0) {
                return $medias;
            }
            $hasNext = $arr['location']['media']['page_info']['has_next_page'];
            $offset = $arr['location']['media']['page_info']['end_cursor'];
        }
        return $medias;
    }

    /**
     * @param string $facebookLocationId
     *
     * @return Location
     * @throws InstagramException
     * @throws InstagramNotFoundException
     */
    public function getLocationById($facebookLocationId)
    {

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::getMediasJsonByLocationIdLink($facebookLocationId),
            $this->generateHeaders($this->userSession));
        if ($response->code === 404) {
            throw new InstagramNotFoundException('Location with this id doesn\'t exist');
        }
        if ($response->code !== 200) {
            throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
        }
        $cookies = self::parseCookies($response->headers['Set-Cookie']);
        $this->userSession['csrftoken'] = $cookies['csrftoken'];
        $jsonResponse = json_decode($response->raw_body, true);
        return Location::create($jsonResponse['location']);
    }

    /**
     * @param bool $force
     *
     * @throws InstagramAuthException
     * @throws InstagramException
     */
    public function login($force = false)
    {
        if ($this->sessionUsername == null || $this->sessionPassword == null) {
            throw new InstagramAuthException("User credentials not provided");
        }

        $cachedString = self::$instanceCache->getItem($this->sessionUsername);
        $session = $cachedString->get();
        if ($force || !$this->isLoggedIn($session)) {

            Request::verifyPeer(false);
            $response = Request::get(Endpoints::BASE_URL);
            if ($response->code !== 200) {
                throw new InstagramException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
            }
            $cookies = self::parseCookies($response->headers['Set-Cookie']);
            $mid = $cookies['mid'];
            $csrfToken = $cookies['csrftoken'];
            $headers = ['cookie'      => "csrftoken=$csrfToken; mid=$mid;",
                        'referer'     => Endpoints::BASE_URL . '/',
                        'x-csrftoken' => $csrfToken,
            ];

            Request::verifyPeer(false);
            $response = Request::post(Endpoints::LOGIN_URL, $headers,
                ['username' => $this->sessionUsername, 'password' => $this->sessionPassword]);

            if ($response->code !== 200) {
                if ((is_string($response->code) || is_numeric($response->code)) && is_string($response->body)) {
                    throw new InstagramAuthException('Response code is ' . $response->code . '. Body: ' . $response->body . ' Something went wrong. Please report issue.');
                } else {
                    throw new InstagramAuthException('Something went wrong. Please report issue.');
                }
            }

            $cookies = self::parseCookies($response->headers['Set-Cookie']);
            $cookies['mid'] = $mid;
            $cachedString->set($cookies);
            self::$instanceCache->save($cachedString);
            $this->userSession = $cookies;
        } else {
            $this->userSession = $session;
        }
    }

    /**
     * @param $session
     *
     * @return bool
     */
    public function isLoggedIn($session)
    {
        if (is_null($session) || !isset($session['sessionid'])) {
            return false;
        }
        $sessionId = $session['sessionid'];
        $csrfToken = $session['csrftoken'];
        $headers = ['cookie'      => "csrftoken=$csrfToken; sessionid=$sessionId;",
                    'referer'     => Endpoints::BASE_URL . '/',
                    'x-csrftoken' => $csrfToken,
        ];

        Request::verifyPeer(false);
        $response = Request::get(Endpoints::BASE_URL, $headers);
        if ($response->code !== 200) {
            return false;
        }
        $cookies = self::parseCookies($response->headers['Set-Cookie']);
        if (!isset($cookies['ds_user_id'])) {
            return false;
        }
        return true;
    }

    /**
     *
     */
    public function saveSession()
    {
        $cachedString = self::$instanceCache->getItem($this->sessionUsername);
        $cachedString->set($this->userSession);
    }

    /**
     * @param string $rawCookies
     *
     * @return array
     */
    private static function parseCookies($rawCookies)
    {
        if (!is_array($rawCookies)) {
            $rawCookies = [$rawCookies];
        }

        $cookies = [];
        foreach ($rawCookies as $c) {
            $c = explode(';', $c)[0];
            $parts = explode('=', $c);
            if (sizeof($parts) >= 2 && !is_null($parts[1])) {
                $cookies[$parts[0]] = $parts[1];
            }
        }
        return $cookies;
    }


    /**
     * @param $session
     *
     * @return array
     */
    private function generateHeaders($session)
    {
        $headers = [];
        if ($session) {
            $cookies = '';
            foreach ($session as $key => $value) {
                $cookies .= "$key=$value; ";
            }
            $headers = [
                'cookie'      => $cookies,
                'referer'     => Endpoints::BASE_URL . '/',
                'x-csrftoken' => $session['csrftoken'],
            ];
        }
        return $headers;
    }
}
