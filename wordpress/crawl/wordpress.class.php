<?php
class wordpressPost
{
    private $post_author = 1;
    private $post_content;
    private $post_title;
    private $post_status = 'publish';
    private $comment_status = 'open';
    private $temp;
    private $ping_status = 'open';
    private $post_name = '';
    private $guid = '';
    private $post_type = 'post';
    private $comment_count = 0;
    public $url;
    private $sql;
    private $link;
    private $domain;
    private $oldLink;
    public function __construct()
    {
    }

    //set catory for article
    public function getUrlCatory()
    {
    }
}
