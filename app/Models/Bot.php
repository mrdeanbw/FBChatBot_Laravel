<?php namespace App\Models;

/**
 * @property Page           $page
 * @property MainMenu       $main_menu
 * @property bool           $enabled
 * @property string         $timezone
 * @property double         $timezone_offset
 * @property array          $tags
 * @property GreetingText   $greeting_text
 * @property WelcomeMessage $welcome_message
 * @property DefaultReply   $default_reply
 * @property array          $users
 * @property User           $current_user
 */
class Bot extends BaseModel
{

    use HasEmbeddedArrayModels;

    public $arrayModels = [
        'page'            => Page::class,
        'main_menu'       => MainMenu::class,
        'greeting_text'   => GreetingText::class,
        'default_reply'   => DefaultReply::class,
        'welcome_message' => WelcomeMessage::class,
    ];
}
