<?php

use phpseclib3\Common\Functions\Strings;

session_start();
require_once 'wp-config.php';
require_once 'simple_html_dom.php';
require_once 'wp-config.php';
require_once 'simple_html_dom.php';
require_once 'wp-includes/pluggable-deprecated.php';
require_once 'GoogleAPI/vendor/autoload.php';
// require_once 'wp-includes/userGoogle.class.php';

define("DEFAULT_LINK", "{$_SERVER['HTTP_HOST']}/wordpress/");
define('charset', 'utf8');


class crawl
{
    private $post_content;
    private $post_title;
    private $url;
    private $linkArticle = '444s';
    private $link;
    private $domain;
    private $oldLink;
    private $id_web;
    private $newsClass;
    private $webiste = array();

    private $userGoogle = array();
    private $token = array();
    private $redirectory = 'http://localhost:8080/wordpress/craw.class.php';
    private $jsonKeyFilePath = "google_api.json";
    private $client;
    private $db;
    private $blogger;

    private $refreshTokenArray = array();
    private $arrayIDBlogger = array();
    private $arrayLink = array('https://vietnamnet.vn/vn/bat-dong-san/');
    private $arrayNew = array('.clearfix.item a');
    private $arrayArticle = array('.ArticleContent');

    public function __construct()
    {
        set_time_limit(0);
        $this->connectDB();
        $this->getAccessTokenFromDB();

        //return array ID blogger to login
        $this->getIDBloggerFromGoogle();

        /**
         * get website from database
         * this is list website isset in database to get news
         * */

        $this->getWebsiteNewsFromDB();
        $this->setNews();

        $this->getTime();

        $this->inputWebsiteCrawl();

        $this->bridge();

        // $this->createGoogleClient();

        //create link login and login 
        // $this->loginGoogle();



        //insert to database information usser gooogle to query post to blogger
        // $this->insertAccessTokenToDB();


    }

    /**
     * bridge for all function in class
     */
    public function bridge()
    {
        /**
         * check link website isset in database
         * return link news isset in database or false if don't have ít
         */
        $this->checkNews($this->convert_vi_to_en($this->link));
        $this->setContent();
    }

    public function inputWebsiteCrawl()
    {
        if (isset($_POST['submit'])) {

            array_push($this->arrayNew, '.' . $_POST['classNews']);
            array_push($this->arrayArticle, '.' . $_POST['classArticle']);
            array_push($this->arrayLink, '.' . $_POST['linkNews']);
            $this->setNews();
        } elseif (isset($_POST['insert'])) {
            $userId_current = get_current_user_id();
            $sql = "INSERT INTO `seo_link_get_news`(`id_user`,`web_name`,`link_home`,`link_news`,`class_news`, `class_article`)VALUES('{$userId_current}','{$_POST['websiteName']}','{$_POST['linkHome']}','{$_POST['linkNews']}','{$_POST['classNews']}', '{$_POST['classArticle']}')";
            $this->db->query($sql);
        }
        echo '<form action="" method="post">
                <input type="text" placeholder="linkHome" name="linkHome" id="">
                <input type="text" placeholder="linkNews" name="linkNews"  id="">
                <input type="text" placeholder="classNews" name="classNews" id="">
                <input type="text" placeholder="classArticle" name="classArticle" id="">
                <input type="text" placeholder="websiteName" name="websiteName" id="">
                <input type="submit" placeholder="submit" name="submit" id="" value="submit">
                <input type="submit" placeholder="insert" name="insert" id="" value="insert">
            </form>';
    }

    /**
     * create google client to access google
     * @return Google_Client to client create account
     */
    public function createGoogleClient()
    {
        //create client
        $this->client = new Google_Client();
        $this->client->setClientId(Google_Client_ID);
        $this->client->setClientSecret(Google_Client_SECRET);
        $this->client->setRedirectUri($this->redirectory);

        //set access type to get offine to get refresh_token
        $this->client->setAccessType('offline');

        ///set to refresh token to refresh access token
        $this->client->setApprovalPrompt('force');

        $this->client->getAccessToken();
        //set json file get from json
        $this->client->setAuthConfig($this->jsonKeyFilePath);

        //set name for web app
        $this->client->setApplicationName('Hệ Thống Seo');

        //get permission Blogger to get list by user
        $this->client->addScope(Google_Service_Blogger::BLOGGER);

        //get information user login email and profile
        $this->client->addScope("email");
        $this->client->addScope("profile");
        return $this->client;
    }

    public function getAccessTokenFromDB()
    {
        $sql = "SELECT `refresh_token` FROM `seo_google_account` WHERE id_user_add = " . (int)get_current_user_id();
        if (is_user_logged_in()) {
            $result = $this->db->query($sql);
            if ($result) {
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $this->createGoogleClient()->refreshToken($row['refresh_token']);
                }
            } else {
            }
        } else {
            header("Location: http://" . DEFAULT_LINK . "wp-login.php");
        }
    }

    public function setAccessTokenFromDB($refreshToken)
    {
        return $this->createGoogleClient()->refreshToken($refreshToken);
    }

    public function insertAccessTokenToDB()
    {
        $id_user_current = get_current_user_id();
        $sql = "INSERT INTO `seo_google_account`(`id_user_add`,`google_name`, `email`, `refresh_token`, `scope`, `token_type`, `id_token`, `id_account_google`) 
        VALUES ('{$id_user_current}','{$this->userGoogle['google_name']}','{$this->userGoogle['email']}','{$this->token['refresh_token']}','{$this->token['scope']}','{$this->token['token_type']}','{$this->token['id_token']}', '{$this->userGoogle['id']}')";

        $result = $this->db->query($sql);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * connect to database to query
     * @return Database object to query
     */
    public function connectDB()
    {
        return $this->db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASSWORD);
    }

    /**
     * insert post to blogger with id and data
     * @param $post_title is string title to blogger
     * @param $post_content is string to post to blogger
     * @param $post_label is label of post to post blogger
     * @param Blogger $idBlogger is id of blogger to insert
     * @return true if insert sessuce to blogger
     */
    public function postNewsBlogger($post_title, $post_content, $post_label, $idBlogger)
    {
        $blogger = new Google_Service_Blogger($this->client);
        $postBlogger = new Google_Service_Blogger_Post();
        $postBlogger->title = $post_title;
        $postBlogger->content = $post_content;
        $postBlogger->labels = $post_label;

        // $arrayData = array(
        //     'title' => $this->getTitle(),
        //     'content' => $this->getContent(),
        //     'id_blogger' => $idBlogger,
        // );

        $post = $blogger->posts->insert($idBlogger, $postBlogger);
        // if ($post) {
        //     return true;
        // } else {
        //     return false;
        // }
    }

    /**
     * request to get id blogger
     * @return array ID Blogger 
     */
    public function getIDBloggerFromGoogle()
    {
        $this->blogger = new Google_Service_Blogger($this->client);

        //get listBlogger using listByUser in google API
        //self is refer to user login to get list blogger
        $parramArray = array(
            'status' => 'LIVE',
        );
        $listBlogger = $this->blogger->blogs->listByUser('self', $parramArray);

        foreach ($listBlogger as $key => $value) {
            array_push($this->arrayIDBlogger, $listBlogger[$key]['id']);
        }
        return $this->arrayIDBlogger;
    }

    // check session to get access token and refresh access token
    public function loginGoogle()
    {
        if (!empty($_SESSION['refreshToken']) && $this->client->isAccessTokenExpired()) {

            //check access token from session to access blogger
            $this->client->refreshToken($_SESSION['refreshToken']);

            $this->token = $this->client->fetchAccessTokenWithRefreshToken();
            $google_oauth = new Google_Service_Oauth2($this->client);
            $google_information = $google_oauth->userinfo->get();

            $this->userGoogle = [
                'id' => $google_information->id,
                'email' => $google_information->email,
                'google_name' => $google_information->name,
            ];
        } else if (isset($_GET['code']) && empty($_SESSION['refresh_token'])) {
            $this->token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
            $_SESSION['refreshToken'] = $this->client->getRefreshToken();
        } else {
            // create link to login to gooogle account.
            echo "<a href='" . $this->client->createAuthUrl() . "'>Google Login</a>";
        }
    }

    /**
     * get Catagory from url
     */
    public function getUrlCatory($url)
    {
    }

    /**
     * get News from url 
     */
    public function setNews()
    {
        foreach ($this->arrayLink as $url) {

            //get html from url
            $html = file_get_html($url);
            $this->url = $url;
            //loop to get newsclass
            foreach ($this->arrayNew as $newsClass) {
                if (!empty($html->find($newsClass))) {
                    $this->newsClass = $newsClass;
                    //get class news
                    $tin = $html->find($newsClass);
                    break;
                }
            }
            $this->getDomain();

            //set result query
            $reLink = 0;
            set_time_limit(0);
            foreach ($tin as $news) {

                // check domain have in href to get link
                if (strpos($news->getAttribute('href'), $this->domain) === 0) {
                    $this->linkArticle = $news->getAttribute('href');

                    // so sanh link dang xet co trung voi link moi chen
                    if ($this->oldLink !== $this->linkArticle) {
                        $this->bridge();
                    }
                } else {
                    $this->linkArticle = $this->domain . '/' . $this->removeBackslachInUrl($news->getAttribute('href'));

                    // so sanh link dang xet co trung voi link moi chen
                    if ($this->oldLink !== $this->linkArticle) {
                        $this->bridge();
                    }
                }
            }
        }
    }
    private function removeBackslachInUrl($url)
    {
        if (substr($url, 0, 1) == '/') {
            return substr($url, 1);
        } else {
            return $url;
        }
    }

    /**
     * check link is isset in database
     */
    public function checkNews($link)
    {

        //query database url from database to the same
        $sql = "SELECT COUNT(ID) FROM seo_posts WHERE post_name = '" . $link . "'";

        //check isse news in database
        // $result = $this->db->query($sql);
        $result = false;
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $_result = $row['COUNT(ID)'];
            }
            return $_result;
        } else {
            return false;
        }
    }

    public function saveAttributeImg($img, $find)
    {
        if (!empty(file_get_contents($find))) {

            if (strpos($find, $this->domain)) {
                $find = $this->domain . '/' . $find;
            }
            //new img src for my database
            $img_src = wp_upload_dir()['url'] . '/' . basename($find);

            //check isset file not download
            if (!file_exists($img_src)) {
                $img_src = explode('?', $img_src);
                $img_src = $img_src[0];

                //set attribute for new img element
                $img->setAttribute('data-src', $img_src);
                $img->setAttribute('src', $img_src);
                $img->setAttribute('srcset', $img_src);
                $img->setAttribute('data-srcset', $img_src);

                //save file img to new src in my host
                $curl_handle = curl_init();
                curl_setopt($curl_handle, CURLOPT_URL, $find);
                curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
                curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl_handle, CURLOPT_USERAGENT, 'He Thong Seo');
                $query = curl_exec($curl_handle);
                curl_close($curl_handle);

                file_put_contents(wp_upload_dir()['path'] . '/' . $this->removeCharacterInBasename(basename($find)), $query);
            }

            //get infor file image 
            $issetFile = getimagesize($img_src);

            /**
             * check file type, if file type is NULL is delete file it
             */
            if (!$issetFile['mime'] || filesize($img_src) < 100) {
                unlink($img_src);
            }

            return true;
        } else {
            return false;
        }
    }


    /**
     * remove character ? in url
     * example: C:\xampp\htdocs\wordpress/wp-content/uploads/2021/05thi-diem-thu-thue-tu-viec-cho-thue-can-ho-mat-bang-kinh-doanh-o-chung-cu.JPG?w=145&h=101
     * remove all character after character ?
     * @param  string $basename is string basename of file
     * @return string $basename was remove charater "?"
     */
    public function removeCharacterInBasename($basename)
    {
        if (strpos($basename, '?') != 0) {
            return substr($basename, 0, strpos($basename, '?'));
        } else {
            return $basename;
        }
    }

    public function validateUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        } else {
            return false;
        }
    }

    public function getImg($find, $img)
    {
        if (strpos(basename($find), '.jpg' || 'png')) {
            if ($this->validateUrl($find)) {
                //set and save attribute img
                $this->saveAttributeImg($img, $find);
            }
        } else {
            $img->outertext = '';
        }
    }

    public function setContent()
    {
        $content = '';
        //check the same link
        $temp = explode('#', $this->linkArticle);
        if ($this->isSameURL($this->url) == false) {
            //check true link - have a website
            if ($this->oldLink != $temp[0]) {
                //refer to inserted now to check isset.
                $this->oldLink = $this->linkArticle;
                //real link is return header is 200
                if ($this->realURL() == 200) {
                    $html = file_get_html($this->linkArticle);
                    foreach ($this->arrayArticle as $item) {
                        $this->insertUrlArticle($this->newsClass, $item);

                        // check content find isset to refer to $tin
                        if (!empty($html->find($item))) {
                            $tin = $html->find($item);
                            break;
                        }
                    }
                    //display error
                    error_reporting(E_ERROR | E_PARSE);

                    foreach ($tin as $article) {

                        // issert link website got from database

                        foreach ($article->find('img') as $img) {

                            //check data src (dynamic src.)
                            if (!empty($find = $img->getAttribute('data-src'))) {
                                // get attribute data-src if isset

                                $find = $img->getAttribute('data-src');
                                $this->getImg($find, $img);
                            } else {

                                //get attribute src if isset
                                $find = $img->getAttribute('src');
                                $this->getImg($find, $img);
                            }
                        };
                        $content = $article;
                    }
                }
            }
        }

        //if content empty is not save
        if (!empty($content)) {
            $this->post_content = strip_tags($content, '<p> <img> <h1> <h2> <h3> <br/>');
            //set title
            $this->setTitle($html);
            // foreach ($this->arrayIDBlogger as $Idblogger) {
            //     $this->postNewsBlogger(
            //         $this->getTitle(),
            //         $this->getContent(),
            //         'Test All Blogger',
            //         $Idblogger
            //     );
            // }


            $arrayPost = array(
                'post_title'    => $this->getTitle(),
                'post_content'  => $this->getContent(),
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_category' => array(8, 39),
                'post_type' => 'post',
                'ping_status' => 'open',
            );

            // insert posts to database
            wp_insert_post($arrayPost, true);
            exit();
        }
    }
    /**
     * set Domain to save img for dynamic src
     */
    public function getDomain()
    {
        //explode url for get domain
        $urlExplode = explode('/', $this->url);

        // get domain
        return $this->domain = $urlExplode[0] . '//' . $urlExplode[1] . $urlExplode[2];
    }

    //insert url article to database to check the same in future
    public function insertUrlArticle($newsClass)
    {
        // $id_user_current = get_current_user_id();
        $sql = "INSERT INTO `seo_link_get_article`
        (
            `id_web`,
            `link_article`
        ) VALUES(
            '{$this->id_web}',
            '{$newsClass}',
        )";

        /**
         * insert url article to database 
         * return true if insert sessuces
         * */

        // if (isset($this->db)) {
        //     // insert url in database to check the same link
        //     $result = $this->db->query($sql);
        //     if (isset($result)) {
        //         return true;
        //     } else {
        //         return false;
        //     }
        // }
    }

    public function realURL()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->linkArticle);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);
        return $headers['http_code'];
    }

    public function isSameURL($url)
    {
        if (isset($this->db)) {
            // check url in database is isset
            $url = 'https://vietnamnet.vn/vn/bat-dong-san/du-an/bao-cao-thu-tuong-xem-xet-de-xuat-dau-tu-du-an-co-casino-o-van-don-736033.html';
            $sql = "SELECT COUNT(id_web) FROM seo_link_get_article WHERE link_article ='{$url}'";

            $result = $this->db->query($sql);
            $result = $result->fetch(PDO::FETCH_ASSOC);

            // is condition $result['id_web'] >= 1

            // if ($result['COUNT(id_web)'] >= 1) {
            //     return true;
            // } else {
            //     return false;
            // };
            return false;
        }
    }

    /**
     * @param $html  is content of website to get title
     * @return true is sessuce
     */
    public function setTitle($html)
    {
        $h1Tag = $html->find('h1');

        $result = $this->post_title = $h1Tag[0]->plaintext;
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function getCatagoryFromUrl()
    {
        $url = explode('/', $this->url);
    }

    public function getTime()
    {
        $time = new DateTime();
        return $this->post_date = date("Y-m-d H:i:s");
    }

    // edit link from vietnamses
    public function create_link()
    {
        $this->link = preg_replace('/\s+/', '', $this->convert_vi_to_en($this->post_title));
    }

    /**
     * insert to database
     * @return true if insert sessuce catagory, update link post and get last id to show
     */
    public function insertCatagory()
    {
        if (isset($this->db)) {
            //get late id when to insert
            $getLastIdInsert = $this->db->lastInsertId();

            //insert catagory for post
            $insertCatagory = $this->db->query("INSERT INTO `seoterm_relationships`(`object_id`, `term_taxonomy_id`, `term_order`) VALUES ( {$getLastIdInsert} ,1,0)");

            if ($getLastIdInsert && $insertCatagory) {
                return true;
            }
        } else {
            echo 'Connect to Database Error or Insert database error';
            return false;
        }
    }

    public function insertLinkPostWp()
    {
        $getLastIdInsert = $this->db->lastInsertId();
        $updateLinkPost = $this->db->query("UPDATE `seoposts` SET `guid`='" . DEFAULT_LINK . "?p" . $getLastIdInsert . "' WHERE ID = " . $getLastIdInsert);
    }

    /**
     * get Title post
     * @return string show to check
     */
    public function getTitle()
    {
        return $this->post_title;
    }

    /**
     * get Content post to check before insert
     * @return post content show to check is string
     */
    public function getContent()
    {
        return $this->post_content;
    }

    /**
     * get link web to get content from website save in database to crawl
     * @return array website link web get from database to crawl all link is array
     */
    public function getWebsiteNewsFromDB()
    {
        $sql = "SELECT id_web, id_user, web_name,link_home, link_news, class_news, class_article FROM seo_link_get_news WHERE seo_link_get_news.id_user = " . get_current_user_id();
        $result = $this->db->query($sql);

        /**
         * loop to push result query to array website
         * it is class to get news and class to get article
         * row is all value
         */
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            array_push($this->arrayLink, $row['link_news']);
            array_push($this->arrayNew, $row['class_news']);
            array_push($this->arrayArticle, $row['class_article']);
        }
        return true;
    }
    /**
     * Convert character vietnamses to english to create link
     * @param string $str is string need to convert
     * @return string converted is string
     *  */
    public function convert_vi_to_en($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
        $str = preg_replace("/(đ)/", "d", $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", "A", $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", "E", $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", "I", $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", "O", $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", "U", $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", "Y", $str);
        $str = preg_replace("/(Đ)/", "D", $str);
        //$str = str_replace(" ", "-", str_replace("&*#39;","",$str));
        return $str;
    }
}
$craw = new crawl();
