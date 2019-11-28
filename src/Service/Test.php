<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Service;

/**
 * Description of Test
 *
 * @author etudiant
 */
class Test {

    public function getPrixMessage($article, $prix){
        $message = "Le nouveau prix de" .$article. " = " .$prix ;    
        return $message;
    }
        
}

