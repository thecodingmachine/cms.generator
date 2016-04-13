<?php

use Mouf\MoufManager;
use Mouf\MoufUtils;

MoufUtils::registerMainMenu('cmsMainMenu', 'CMS', null, 'mainMenu', 70);
MoufUtils::registerMenuItem('cmsSubMenu', 'Generator', null, 'cmsMainMenu', 80);
MoufUtils::registerChooseInstanceMenuItem('cmsGeneratorSubSubMenu', 'Generate new CMS component', 'cmsadmin/', 'Mouf\\Cms\\Generator\\CmsGenerator', 'cmsSubMenu', 10);

// Controller declaration
$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('cmsadmin', 'Mouf\\CMS\\Generator\\Controllers\\CmsGeneratorController', true);
$moufManager->bindComponents('cmsadmin', 'template', 'moufTemplate');
$moufManager->bindComponents('cmsadmin', 'content', 'block.content');