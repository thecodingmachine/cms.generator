<?php

use Mouf\MoufManager;
use Mouf\MoufUtils;

MoufUtils::registerMainMenu('cmsMainMenu', 'CMS', null, 'mainMenu', 70);
MoufUtils::registerMenuItem('cmsSubMenu', 'Scaffolder', null, 'cmsMainMenu', 80);
MoufUtils::registerChooseInstanceMenuItem('cmsScaffolderSubSubMenu', 'Generate new CMS component', 'cmsadmin/', 'Mouf\\Database\\TDBM\\TDBMService', 'cmsSubMenu', 10);

// Controller declaration
$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('cmsadmin', 'Mouf\\Cms\\Scaffolder\\Controllers\\CmsScaffolderController', true);
$moufManager->bindComponents('cmsadmin', 'template', 'moufTemplate');
$moufManager->bindComponents('cmsadmin', 'content', 'block.content');