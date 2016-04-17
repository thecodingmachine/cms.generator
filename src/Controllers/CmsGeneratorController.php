<?php
namespace Mouf\Cms\Generator\Controllers;

use Mouf\Cms\Generator\Services\CMSControllerGeneratorService;
use Mouf\Controllers\AbstractMoufInstanceController;
use Mouf\Database\Patcher\DatabasePatchInstaller;
use Mouf\Database\TDBM\TDBMService;
use Mouf\Html\Widgets\MessageService\Service\UserMessageInterface;
use Mouf\InstanceProxy;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlBlock;
use Psr\Log\LoggerInterface;
use \Twig_Environment;
use Mouf\Html\Renderer\Twig\TwigTemplate;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * This controller generates Cms components
 */
class CmsGeneratorController extends AbstractMoufInstanceController {

    /**
     * The main content block of the page.
     * @var HtmlBlock
     */
    public $content;

    /**
     * @Action
     * @Logged
     */
    public function index($name, $selfedit = "false") {

        $this->initController($name, $selfedit);

        $this->selfedit = $selfedit;
        $this->content->addFile(__DIR__.'/../views/cmsGenerator.php', $this);
        $this->template->toHtml();
    }

    /**
     * This action generates the DAOs and Beans for the TDBM service passed in parameter.
     *
     * @Action
     *
     * @param string $name
     * @param string $componentName
     * @param string $selfedit
     *
     * @return RedirectResponse
     */
    public function componentGenerate($name, $componentName, $selfedit = 'false')
    {
        $this->initController($name, $selfedit);

        if(null == $componentName){
            set_user_message("You must define a component name.", UserMessageInterface::ERROR);
            header('Location: '.ROOT_URL.'cmsadmin/?name='.$name);
        }
        self::sqlGenerate($componentName);

        $uniqueName = "cms-generator-component-".$componentName;

        DatabasePatchInstaller::registerPatch($this->moufManager,
            $uniqueName,
            "Creating the table for the new CMS component : ".$componentName,
            "database/up/".date('YmdHis')."-patch.sql", // SQL patch file, relative to ROOT_PATH
            "database/down/".date('YmdHis')."-patch.sql"); // Optional SQL revert patch file, relative to ROOT_PATH

        $this->moufManager->rewriteMouf();

        $patchService = new InstanceProxy('patchService', $selfedit == 'true');
        $patchService->apply($uniqueName);

        $daonamespace = $this->moufManager->getVariable('tdbmDefaultDaoNamespace');
        $beannamespace = $this->moufManager->getVariable('tdbmDefaultBeanNamespace');
        $daofactoryclassname = $this->moufManager->getVariable('tdbmDefaultDaoFactoryName');
        $storeInUtc = $this->moufManager->getVariable('tdbmDefaultStoreInUtc');
        $useCustomComposer = $this->moufManager->getVariable('tdbmDefaultUseCustomComposer');
        $composerFile = $this->moufManager->getVariable('tdbmDefaultComposerFile');

        $tdbmService = new InstanceProxy($name);
        /* @var $tdbmService TDBMService */
        $tdbmService->generateAllDaosAndBeans($daofactoryclassname, $daonamespace, $beannamespace, $storeInUtc, ($useCustomComposer ? $composerFile : null));

        $this->moufManager->rewriteMouf();

        $rootPath = realpath(ROOT_PATH.'../../../').'/';
        $beanFileName = $rootPath."src/".$beannamespace."/".ucfirst($componentName)."Bean.php"; // Ex: src/Model/Bean/ComponentnameBean.php

        if(!file_exists($beanFileName)){
            set_user_message("Fail on editing the Bean", UserMessageInterface::ERROR);
            header('Location: '.ROOT_URL.'cmsadmin/?name='.$name);
        }

        $fileContent = file_get_contents($beanFileName);

        $useBaseBean = ucfirst($componentName)."BaseBean;";
        $useBaseBeanLength = strlen($useBaseBean);
        $useBaseBeanPos = strpos($fileContent, $useBaseBean); // Search the position of the "ComponentnameBaseBean" in the fileContent

        $useTraitInterface = "\nuse Mouf\\Cms\\Generator\\Utils\\CmsTrait;\nuse Mouf\\Cms\\Generator\\Utils\\CmsInterface;\nuse Mouf\\Html\\HtmlElement\\HtmlElementInterface;\nuse Mouf\\Html\\Renderer\\Renderable;\n";
        $fileContent = substr_replace($fileContent, $useTraitInterface, $useBaseBeanPos+$useBaseBeanLength, 0); // Insert the string in the fileContent

        $extendsBaseBean = "extends ".ucfirst($componentName)."BaseBean";
        $extendsBaseBeanLength = strlen($extendsBaseBean);
        $extendsBaseBeanPos = strpos($fileContent, $extendsBaseBean); // Search the position of the "extends ComponentnameBaseBean" in the fileContent

        $implementsCmsInsterFace = " implements CmsInterface, HtmlElementInterface {\n    use CmsTrait;\n    use Renderable;\n\n";
        $fileContent = substr_replace($fileContent, $implementsCmsInsterFace, $extendsBaseBeanPos+$extendsBaseBeanLength, -1); // Replace the string in the fileContent

        file_put_contents($beanFileName, $fileContent);


        $namespace = $this->moufManager->getVariable('splashDefaultControllersNamespace');
        $viewDir = $this->moufManager->getVariable('splashDefaultViewsDirectory');
        $controllerGenerator  = new CMSControllerGeneratorService();

        $actions = [
            [
                'view' => 'twig',
                'url' => '/list',
                'anyMethod' => false,
                'getMethod' => true,
                'postMethod' => false,
                'putMethod' => false,
                'deleteMethod' => false,
                'method' => 'displayItemsList',
                'twigFile' => $viewDir.'list.twig',
                'code' =>
                    '
                    $items = $this->daoFactory->get'.ucfirst($componentName).'Dao()->findAll();
                    
                    '
            ],
            [
                'view' => 'twig',
                'url' => '/edit',
                'parameters' => [
                    [
                        'optionnal' => false,
                        'type' => 'int',
                        'name' => 'id',
                    ]
                ],
                'anyMethod' => false,
                'getMethod' => true,
                'postMethod' => false,
                'putMethod' => false,
                'deleteMethod' => false,
                'method' => 'edit',
                'twigFile' => $viewDir.'edit.twig',
                'code' =>
                    '
                    $item = $this->daoFactory->get'.ucfirst($componentName).'Dao()->getById($id);
                    $item->setContext("displayBack");
                    $this->content->addHtmlElement($item);
                    '
            ],
            [
                'view' => 'redirect',
                'url' => '/save',
                'parameters' => [
                    [
                        'optionnal' => false,
                        'type' => 'int',
                        'name' => 'id',
                    ],
                ],
                'anyMethod' => false,
                'getMethod' => false,
                'postMethod' => true,
                'putMethod' => false,
                'deleteMethod' => false,
                'method' => 'save',
                'redirect' => '/list',
                'code' =>
                    '
                    $items = $this->daoFactory->get'.ucfirst($componentName).'Dao()->findAll();
                    
                    '
            ],
        ];
        $controllerGenerator->generate($this->moufManager, $componentName.'Controller', strtolower($componentName).'Controller', $namespace, false, true, true, $actions);

        set_user_message("Component successfully created.", UserMessageInterface::SUCCESS);
        header('Location: '.ROOT_URL.'cmsadmin/?name='.$name);
    }

    /**
     * This action generates the DAOs and Beans for the TDBM service passed in parameterd
     *
     * @param string $componentName
     * @return string
     */
    public function sqlGenerate($componentName)
    {
        $rootPath = realpath(ROOT_PATH.'../../../').'/';

        $upSqlFileName = "database/up/".date('YmdHis')."-patch.sql";
        $downSqlFileName = "database/down/".date('YmdHis')."-patch.sql";

        $baseDirUpSqlFile = dirname($rootPath.$upSqlFileName);
        $baseDirDownSqlFile = dirname($rootPath.$downSqlFileName);

        $upSql = "CREATE TABLE IF NOT EXISTS `".htmlspecialchars($componentName)."` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `title` varchar(255) NOT NULL DEFAULT '',
                          `slug` varchar(255) NOT NULL DEFAULT '',
                          `short_text` text  DEFAULT NULL,
                          `content` text  DEFAULT NULL,
                          `image` text  DEFAULT NULL,
                          PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $downSql = "DROP TABLE IF EXISTS `".htmlspecialchars($componentName)."`";

        // Let's create the directory
        if (!file_exists($baseDirUpSqlFile)) {
            $old = umask(0);
            $result = @mkdir($baseDirUpSqlFile, 0775, true);
            umask($old);
            if (!$result) {
                set_user_message("Sorry, impossible to create directory '".plainstring_to_htmlprotected($baseDirUpSqlFile)."'. Please check directory permissions.");
                //header('Location: .?name='.urlencode($name).'&selfedit='.urlencode($selfedit).($patchInstanceName ? '&patchInstanceName='.$patchInstanceName : ''));

                return;
            }
        }

        if (!is_writable($baseDirUpSqlFile)) {
            set_user_message("Sorry, directory '".plainstring_to_htmlprotected($baseDirUpSqlFile)."' is not writable. Please check directory permissions.");
            //header('Location: .?name='.urlencode($name).'&selfedit='.urlencode($selfedit).($patchInstanceName ? '&patchInstanceName='.$patchInstanceName : ''));

            return;
        }

        // Let's create the directory
        if (!file_exists($baseDirDownSqlFile)) {
            $old = umask(0);
            $result = @mkdir($baseDirDownSqlFile, 0775, true);
            umask($old);
            if (!$result) {
                set_user_message("Sorry, impossible to create directory '".plainstring_to_htmlprotected($baseDirDownSqlFile)."'. Please check directory permissions.");
                //header('Location: .?name='.urlencode($name).'&selfedit='.urlencode($selfedit).($patchInstanceName ? '&patchInstanceName='.$patchInstanceName : ''));

                return;
            }
        }

        if (!is_writable($baseDirDownSqlFile)) {
            set_user_message("Sorry, directory '".plainstring_to_htmlprotected($baseDirDownSqlFile)."' is not writable. Please check directory permissions.");
            //header('Location: .?name='.urlencode($name).'&selfedit='.urlencode($selfedit).($patchInstanceName ? '&patchInstanceName='.$patchInstanceName : ''));

            return;
        }

        file_put_contents($rootPath.$upSqlFileName, $upSql);
        // Chmod may fail if the file does not belong to the Apache user.
        @chmod($rootPath.$upSqlFileName, 0664);

        file_put_contents($rootPath.$downSqlFileName, $downSql);
        // Chmod may fail if the file does not belong to the Apache user.
        @chmod($rootPath.$downSqlFileName, 0664);

    }
}
