<?php

// @todo - fix this class
namespace Maltyst;

use League\Plates\Engine;
// use Mustache_Engine;
// use Mustache_Loader_FilesystemLoader;

if (!defined('ABSPATH')) {
    exit;
}
class ViewController
{
    private string $htmlDir;
    private Engine $platesEngine;
    // private Mustache_Engine $mustacheEngine;
    private $mustacheEngine;

    private Database $db;
    private MauticAccess $mauticAccess;

    private Utils $utils;
    private SettingsUtils $settingsUtils;

    public function __construct(Database $db, Utils $utils, MauticAccess $mauticAccess, SettingsUtils $settingsUtils)
    {   
        //HTML PHP rendering for views
        $this->htmlDir  = __DIR__ . '/../../html-views';
        $this->platesEngine = new \League\Plates\Engine($this->htmlDir, 'phtml');

        // Ill go ahead and disable this, I think this could just be rendered on the fly from the
        // mjml server?
        // //HTML MUSTACHE rendering for emails 
        // $mOptions =  ['extension' => '.html'];
        // $this->mustacheEngine = new Mustache_Engine(array(
        //     'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../dist/html', $mOptions),
        // ));


        $this->db = $db;
        $this->mauticAccess = $mauticAccess;

        $this->utils = $utils;
        $this->settingsUtils = $settingsUtils;
    }

    private function render($tpl, array $data=[]): string
    {
        ob_start();
        $html = $this->platesEngine->render($tpl, $data);
        echo $html;
        return ob_get_clean();
    }



    public function renderConfirmation($attr, $content, $tag): string
    {
        $tpl = 'confirmation';

        $data = [
            'prefix'   => MALTYST_PREFIX,
            //'formType' => $tpl,
        ];

        return $this->render($tpl);
    }

    public function renderPreferenceCenter($attr, $content, $tag): string
    {
        $tpl = 'preference-center';

        $data = [
            'prefix'   => MALTYST_PREFIX,
            //'formType' => $tpl,
        ];

        return $this->render($tpl, $data);
    }
    

    public function renderOptinForm($attr, $content, $tag): string
    {
        $tpl = 'optin';

        $data = [
            'prefix'   => MALTYST_PREFIX,
            //'formType' => $tpl,
        ];

        return $this->render($tpl, $data);
    }


    public function maltystCustomExcerptLength() : int {
        $excerptLen = mb_trim($this->settingsUtils->getSettingsValue('maltystPostPublishNotifyExcerptLen'));
        $excerptLen = preg_match('/^[1-9][0-9]*$/', $excerptLen) === 1 ? (int)$excerptLen : 150;

        return $excerptLen;
    }

    public function utilGetPostEmailData($post): array
    {

        // Post info
        //==============================================================================
        $postId     = $post->ID;
        $postTitle  = empty($post->post_title) ? '' : $post->post_title;
        $postIntro  = empty($post->post_excerpt) ? '' : $post->post_excerpt;
        
        //Automatically generate excerpt from content if not present.
        if (empty($postIntro)) {

            //Modify - so we remove read more from excerpts
            function maltyst_link_excerpt( $more ) {
                return '&hellip; ';
            }
            add_filter( 'excerpt_more', 'maltyst_link_excerpt', 999 );
            
            
            //Need to expand excerpt length longer - when autogenerated
            add_filter( 'excerpt_length', [$this, 'maltystCustomExcerptLength'], 999 );
            
            //Getting autogenerated post intro
            $postIntro = wp_trim_excerpt('', $post);
            
            //We should remember to disable these overrides right after - as we do not
            //want any other functionality to be overridden 
            remove_filter( 'excerpt_more', 'maltyst_link_excerpt' );
            remove_filter( 'excerpt_length', [$this, 'maltystCustomExcerptLength'] );
        }
        $postUrl = get_permalink($post, $leavename = false);

        $postDate = get_the_date('', $post);
        $postTime = get_the_time('', $post);
        $postCommentsLink = get_comments_link($post);
        $postReplyLink    = $postUrl . '#respond';
        $postCommentsNum  = get_comments_number($post);

        //Get tag links
        $tags = wp_get_post_tags($post->ID);
        $postTags = [];
        $postTagsHtml = [];
        foreach ( $tags as $tag ) {
            $tagLink = get_tag_link($tag->term_id);
            $tagData = [
                'link' => $tagLink,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ];
            $postTags[] = $tagData;
            $style = 'style="color: #2585b2; text-decoration: underline;"';
            $postTagsHtml[] = '<a ' . $style . ' class="link-style-blue" href="' . htmlspecialchars($tagLink, \ENT_QUOTES) . '">' . htmlspecialchars($tag->name) . '</a>';
        }
        $postTagsHtml = implode(', ', $postTagsHtml);

        //Category links
        $categories = wp_get_post_categories($post->ID);
        $postCategories = [];
        $postCategoriesHtml = [];
        foreach ( $categories as $category ) {
            $catLink = get_category_link($category);
            $cat = get_category( $category );
            $catData = [
                'link' => $catLink,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ];
            $postCategories[] = $catData;
            $postCategoriesHtml[] = '<a ' . $style . ' class="link-style-blue" href="' . htmlspecialchars($catLink, \ENT_QUOTES) . '">' . htmlspecialchars($cat->name) . '</a>';
        }
        $postCategoriesHtml = implode(', ', $postCategoriesHtml);
        

        // Author info
        //==============================================================================
        $user  = get_user_by('id', (int)$post->post_author);
        $authorDisplayName = $user->data->display_name;
        $authorUrl         = $user->data->user_url;

        $args = [
            'size' => 120
        ];
        $authorPicUrl = get_avatar_url( (int)$post->post_author, $args );
        

        // Blog info
        //==============================================================================
        $blogTitle = get_bloginfo('name');
        $blogDesc  = get_bloginfo('description');
        $blogUrl   = get_bloginfo('url');





        // Combined info
        //==============================================================================
        $maltystPcUrl = $this->settingsUtils->getSettingsValue('maltystPcUrl');
        $userDetails = [
            //Blog info
            'blogTitle'   => $blogTitle,
            'blogDesc'    => $blogDesc,
            'blogUrl'     => $blogUrl,
            'blogLogoUrl' => $this->settingsUtils->getSettingsValue('maltystBlogLogoUrl'),


            //Unsubscribe links
            'unsubUrl'  => $blogUrl . $maltystPcUrl . '?maltyst_contact_uqid={contactfield=maltyst_contact_uqid}&unsubscribe-from-all=true',
            'pcUrl'     => $blogUrl . $maltystPcUrl . '?maltyst_contact_uqid={contactfield=maltyst_contact_uqid}',


            //Post info
            'postId'             => $postId,
            'postTitle'          => $postTitle,
            'postIntro'          => $postIntro,
            'postUrl'            => $postUrl,
            'postDate'           => $postDate,
            'postTime'           => $postTime,
            'postTags'           => $postTags,
            'postCategories'     => $postCategories,
            'postCommentsLink'   => $postCommentsLink,
            'postCommentsNum'    => $postCommentsNum,
            'postReplyLink'      => $postReplyLink,
            'postCategoriesHtml' => $postCategoriesHtml,
            'postTagsHtml'       => $postTagsHtml,

            //Author details
            'authorDisplayName' => $authorDisplayName,
            'authorPicUrl'      => $authorPicUrl,
            'authorUrl'         => $authorUrl,
        ];
        $data = $userDetails;
        $data['prefix'] = MALTYST_PREFIX;

        return $data;
    }


    // //This is not currently being used.
    // //I think better approch would be to pull rendered email from mautic and display that.
    // //Some app-caching would be preferred in that case, we should also include maltyst unique id to render unique links.
    public function emailPostBrowserView(): string
    {
        $postId = isset($_GET['post_id']) ? $_GET['post_id'] : null;
        $post = get_post($postId);

        $data = $this->utilGetPostEmailData($post);

        $tpl = 'email-template-newpost';

        return $this->mustacheEngine->render($tpl, $data);
    }

    


    public function notifyOfNewPost(string $new_status, string $old_status, $post): void
    {
        //Status not changing to published? - nothing to do.
        if ('publish' !== $new_status ) {
            return;
        }

        //Old status was also publish? Let's not re-email then
        if ('publish' === $old_status) {
            return;
        }

        //Fetch all the data for email
        $data = $this->utilGetPostEmailData($post);



        // Render email template
        //==============================================================================
        $tpl = 'email-template-newpost';
        $html = $this->mustacheEngine->render($tpl, $data);

        //Send this email to segment
        //==============================================================================
        list($apiStatus1, $apiResult1) = $this->mauticAccess->getSegmentsToIdReference();
        if (!$apiStatus1) {
            throw new \Exception('Unable to access mautic and get segment to id reference');
            return;
        }

        $maltystPostPublishNotifySegmentName = $this->settingsUtils->getSettingsValue('maltystPostPublishNotifySegmentName');
        $segmentId = isset($apiResult1[$maltystPostPublishNotifySegmentName]) ? (int)$apiResult1[$maltystPostPublishNotifySegmentName] : null;
        if (is_null($segmentId)) {
            throw new \Exception("Segment with following alias: `$maltystPostPublishNotifySegmentName` not found");
            return;
        }
        $emailData = [
            'name'           => 'post-published-' . $data['postId'] . '-' . date('Y-m-d.H-I-s'),
            'subject'        => '[New post] on ' . $data['blogTitle'],

            'isPublished'    => true,
            'publishUp'      => null,
            'publishDown'    => null,
            'template'       => 'mautic_code_mode',
            'language'       => 'en',
            'customHtml'     => $html,
            'plainText'      => '',
            'emailType'      => 'list',
            'category'       => null,
            'lists'          => [$segmentId]
        ];

        //Some optional email headers - if we want to override what's configured in mautic
        $maltystPostPublishNotifyFromAddress = $this->settingsUtils->getSettingsValue('maltystPostPublishNotifyFromAddress');
        $maltystPostPublishNotifyFromName    = $this->settingsUtils->getSettingsValue('maltystPostPublishNotifyFromName');
        $maltystPostPublishNotifyReplyTo     = $this->settingsUtils->getSettingsValue('maltystPostPublishNotifyReplyTo');
        if (!empty($maltystPostPublishNotifyFromAddress)) {
            $emailData['fromAddress'] = $maltystPostPublishNotifyFromAddress;
        }
        if (!empty($maltystPostPublishNotifyFromName)) {
            $emailData['fromName'] = $maltystPostPublishNotifyFromName;
        }
        if (!empty($maltystPostPublishNotifyReplyTo)) {
            $emailData['replyToAddress'] = $maltystPostPublishNotifyReplyTo;
        }


        $this->mauticAccess->sendPostNotificicationToSegment($emailData);
    }
}