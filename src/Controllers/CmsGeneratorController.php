<?php
namespace Mouf\Cms\Generator\Controllers;

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
 * TODO: write controller comment
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
            //header('Location: '.ROOT_URL.'cmsadmin/?name='.$name);
            return new RedirectResponse(ROOT_URL.'cmsadmin/?name='.$name);
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

        set_user_message("OK.", UserMessageInterface::SUCCESS);
        //header('Location: '.ROOT_URL.'cmsadmin/?name='.$name);
        return new RedirectResponse(ROOT_URL.'cmsadmin/?name='.$name);
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

        $upSql = "CREATE TABLE IF NOT EXISTS `".$componentName."` (
                          `id` int(11) NOT NULL AUTO_INCREMENT,
                          `title` varchar(255) NOT NULL DEFAULT '',
                          `slug` varchar(255) NOT NULL DEFAULT '',
                          `short_text` text NOT NULL DEFAULT '',
                          `content` text NOT NULL DEFAULT '',
                          `image` text NOT NULL DEFAULT '',
                          PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        $downSql = "DROP TABLE IF EXISTS `".$componentName."`";

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
