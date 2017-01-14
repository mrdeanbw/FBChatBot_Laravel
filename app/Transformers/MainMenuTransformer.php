<?php
namespace App\Transformers;


use App\Models\MainMenu;

class MainMenuTransformer extends BaseTransformer
{

    protected $defaultIncludes = ['message_blocks'];
    
    public function transform(MainMenu $mainMenu)
    {
        return [
            'id' => $mainMenu->id,
        ];
    }
}