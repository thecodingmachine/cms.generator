<?php

use Mouf\MoufManager;
use Mouf\MoufUtils;

MoufUtils::registerMainMenu('cmsMainMenu', 'CMS', null, 'mainMenu', 70);
MoufUtils::registerChooseInstanceMenuItem('cmsGeneratorSubMenu', 'Generator', 'tdbmadmin/', 'Mouf\\Cms\\Generator\\CmsGenerator', 'cmsMainMenu', 10);

// Controller declaration
$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('tdbmadmin', 'Mouf\\CMS\\Generator\\Controllers\\CmsGeneratorController', true);
$moufManager->bindComponents('tdbmadmin', 'template', 'moufTemplate');
$moufManager->bindComponents('tdbmadmin', 'content', 'block.content');