<?php
namespace Mouf\Cms\Scaffolder\Services;

use Mouf\Composer\ClassNameMapper;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Mvc\Splash\Services\SplashCreateControllerServiceException;
use Mouf\Mvc\Splash\Utils\SplashException;
use Psr\Log\LoggerInterface;
use Mouf\MoufManager;
use Mouf\MoufCache;

/**
 * The service used to create controllers in Splash.
 */
class CMSControllerGeneratorService
{
    /**
     * Generates a controller, view, and sets the instance up.
     *
     * @param MoufManager $moufManager
     * @param string      $controllerName
     * @param string      $instanceName
     * @param string      $namespace
     * @param string      $componentName
     * @param bool        $injectLogger
     * @param bool        $injectTemplate
     * @param bool        $injectDaoFactory
     * @param array       $actions
     *
     * @throws SplashCreateControllerServiceException
     * @throws SplashException
     * @throws \Mouf\MoufException
     */
    public function generate(MoufManager $moufManager, string $controllerName, string $instanceName, string $namespace, string $componentName, bool $injectLogger = false,
                             bool $injectTemplate = false, bool $injectDaoFactory = false, array $actions = array())
    {
        $namespace = rtrim($namespace, '\\').'\\';

        $classNameMapper = ClassNameMapper::createFromComposerFile(__DIR__.'/../../../../../composer.json');
        $possibleFileNames = $classNameMapper->getPossibleFileNames($namespace.$controllerName);
        if (!isset($possibleFileNames[0])) {
            throw new SplashException("The class '".$namespace.$controllerName."' cannot be loaded using rules defined in composer autoload section");
        }
        $fileName = $possibleFileNames[0];
        $controllerPhpDirectory = dirname($fileName);
        $errors = array();
        if (!preg_match('/^[a-z_]\w*$/i', $controllerName)) {
            $errors['controllerNameError'] = 'This is not a valid PHP class name.';
        }
        if (!preg_match('/^[a-z_][\w\\\\]*$/i', $namespace)) {
            $errors['namespaceError'] = 'This is not a valid PHP namespace.';
        }

        $namespace = trim($namespace, '\\');

        if (!file_exists(ROOT_PATH.'../database.tdbm') && $injectDaoFactory) {
            $injectDaoFactory = false;
        }

        // Check that instance does not already exists
        if ($moufManager->has($instanceName)) {
            $errors['instanceError'] = 'This instance already exists.';
        }

        $injectTwig = false;
        $importJsonResponse = false;
        $importHtmlResponse = false;
        $importRedirectResponse = false;
        $importResponse = false;

        foreach ($actions as $key => $action) {
            // Check if the view file exists
            if ($injectTemplate && $action['view'] == 'twig') {
                $injectTwig = true;
                $importHtmlResponse = true;
            }
            if ($injectTemplate && $action['view'] == 'php') {
                $importHtmlResponse = true;
            }
            if ($action['view'] == 'redirect') {
                if (!isset($action['redirect']) || empty($action['redirect'])) {
                    $errors['actions'][$key]['redirectError'] = 'Redirection URL cannot be empty.';
                }
                $importRedirectResponse = true;
            }
            if ($action['view'] == 'json') {
                $importJsonResponse = true;
            }
            if ($action['view'] == 'response') {
                $importResponse = true;
            }
        }

        // TODO: check that URLs are not in error.


        if (!$errors) {
            $result = $this->createDirectory(ROOT_PATH.'../../../'.$controllerPhpDirectory);
            if (!$result) {
                $errors['namespaceError'] = 'Unable to create directory: "'.$controllerPhpDirectory.'"';
            } elseif (file_exists(ROOT_PATH.'../../../'.$controllerPhpDirectory.$controllerName.'.php')) {
                $errors['namespaceError'] = 'The file "'.$controllerPhpDirectory.$controllerName.'.php already exists."';
            } elseif (!is_writable(ROOT_PATH.'../../../'.$controllerPhpDirectory)) {
                $errors['namespaceError'] = 'Unable to write file in directory: "'.$controllerPhpDirectory.'"';
            }

            if (!$errors) {
                ob_start();
                echo '<?php
';
                ?>
namespace <?= $namespace ?>;

use Mouf\Mvc\Splash\Controllers\Controller;
use <?= $moufManager->getVariable('tdbmDefaultBeanNamespace').'\\'.ucfirst($componentName).'Bean' ?>;
use Mouf\Html\Widgets\MessageService\Service\UserMessageInterface;
use Mouf\Html\Utils\WebLibraryManager\WebLibrary;
use Psr\Http\Message\UploadedFileInterface;
<?php if ($injectTemplate) {
    ?>
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlBlock;
<?php

}
                ?>
<?php if ($injectLogger) {
    ?>
use Psr\Log\LoggerInterface;
<?php

}
                ?>
<?php if ($injectDaoFactory) {
    ?>
use <?= $moufManager->getVariable('tdbmDefaultDaoNamespace').'\\Generated\\'.$moufManager->getVariable('tdbmDefaultDaoFactoryName') ?>;
<?php

}
                ?>
<?php if ($injectTwig) {
    ?>
use \Twig_Environment;
use Mouf\Html\Renderer\Twig\TwigTemplate;
<?php

}
                ?>
<?php if ($importJsonResponse) {
    ?>
use Zend\Diactoros\Response\JsonResponse;
<?php

}
                ?>
<?php if ($importRedirectResponse) {
    ?>
use Zend\Diactoros\Response\RedirectResponse;
<?php

}
                ?>
<?php if ($importHtmlResponse) {
    ?>
use Mouf\Mvc\Splash\HtmlResponse;
<?php

}
                ?>
<?php if ($importResponse) {
    ?>
use Zend\Diactoros\Response;
<?php

}
                ?>

/**
* TODO: write controller comment
*/
class <?= $controllerName ?> extends Controller {

<?php if ($injectLogger) {
    ?>
    /**
     * The logger used by this controller.
     * @var LoggerInterface
     */
    private $logger;

<?php

}
                ?>
<?php if ($injectTemplate) {
    ?>
    /**
     * The template used by this controller.
     * @var TemplateInterface
     */
    private $template;

    /**
     * The main content block of the page.
     * @var HtmlBlock
     */
    private $content;

<?php

}
                ?>
<?php if ($injectDaoFactory) {
    ?>
    /**
     * The DAO factory object.
     * @var DaoFactory
     */
    private $daoFactory;

<?php

}
                ?>
<?php if ($injectTwig) {
    ?>
    /**
     * The Twig environment (used to render Twig templates).
     * @var Twig_Environment
     */
    private $twig;

<?php

}
                ?>

    /**
     * Controller's constructor.
    <?php
    if ($injectLogger) {
        echo "     * @param LoggerInterface \$logger The logger\n";
    }
                if ($injectTemplate) {
                    echo "     * @param TemplateInterface \$template The template used by this controller\n";
                    echo "     * @param HtmlBlock \$content The main content block of the page\n";
                }
                if ($injectDaoFactory) {
                    echo "     * @param DaoFactory \$daoFactory The object in charge of retrieving DAOs\n";
                }
                if ($injectTwig) {
                    echo "     * @param Twig_Environment \$twig The Twig environment (used to render Twig templates)\n";
                }
                ?>
     */
    public function __construct(<?php
$parameters = array();
                if ($injectLogger) {
                    $parameters[] = 'LoggerInterface $logger';
                }
                if ($injectTemplate) {
                    $parameters[] = 'TemplateInterface $template';
                    $parameters[] = 'HtmlBlock $content';
                }
                if ($injectDaoFactory) {
                    $parameters[] = 'DaoFactory $daoFactory';
                }
                if ($injectTwig) {
                    $parameters[] = 'Twig_Environment $twig';
                }
                echo implode(', ', $parameters);
                ?>) {
<?php if ($injectLogger) {
    ?>
        $this->logger = $logger;
<?php

}
                if ($injectTemplate) {
                    ?>
        $this->template = $template;
        $this->content = $content;
<?php

                }
                if ($injectDaoFactory) {
                    ?>
        $this->daoFactory = $daoFactory;
<?php

                }
                if ($injectTwig) {
                    ?>
        $this->twig = $twig;
<?php

                }
                ?>
    }

<?php foreach ($actions as $action):
    // First step, let's detect the {parameters} in the URL and add them if necessarry
    // TODO
    // TODO
    // TODO
    // TODO

    ?>
    /**
     * @URL <?= $action['url'] ?>

    <?php if ($action['anyMethod'] == false) {
    if ($action['getMethod'] == true) {
        echo "* @Get\n";
    }
    if ($action['postMethod'] == true) {
        echo "* @Post\n";
    }
    if ($action['putMethod'] == true) {
        echo "* @Put\n";
    }
    if ($action['deleteMethod'] == true) {
        echo "* @Delete\n";
    }
}
                if (isset($action['requiresRight'])) {
                    echo "     * @RequiresRight(name='".$action['requiresRight']."')\n";
                }

                if (isset($action['parameters'])) {
                    $parameters = $action['parameters'];
                    foreach ($parameters as $parameter) {
                        echo '     * @param '.$parameter['type'].' $'.$parameter['name']."\n";
                    }
                } else {
                    $parameters = array();
                }
                if ($injectTemplate && ($action['view'] == 'twig' || $action['view'] == 'php')) {
                    echo "    * @return HtmlResponse\n";
                } elseif ($action['view'] == 'json') {
                    echo "    * @return JsonResponse\n";
                } elseif ($action['view'] == 'redirect') {
                    echo "    * @return RedirectResponse\n";
                } elseif ($action['view'] == 'response') {
                    echo "    * @return Response\n";
                }
                ?>
    */
    public function <?= $action['method'] ?>(<?php
    $parametersCode = array();
                foreach ($parameters as $parameter) {
                    $parameterCode = $parameter['type'].' $'.$parameter['name'];
                    if ($parameter['optionnal'] == 'true') {
                        if ($parameter['type'] == 'int') {
                            $defaultValue = (int) $parameter['defaultValue'];
                        } elseif ($parameter['type'] == 'number') {
                            $defaultValue = (float) $parameter['defaultValue'];
                        } else {
                            $defaultValue = $parameter['defaultValue'];
                        }
                        $parameterCode .= ' = '.var_export($defaultValue, true);
                    }
                    $parametersCode[] = $parameterCode;
                }
                echo implode(', ', $parametersCode);
                ?>)
    <?php if ($injectTemplate && $action['view'] == 'twig'): ?>
    : HtmlResponse
<?php elseif ($injectTemplate && $action['view'] == 'php'): ?>
    : HtmlResponse
<?php elseif ($action['view'] == 'json'): ?>
    : JsonResponse
<?php elseif ($action['view'] == 'redirect'): ?>
    : RedirectResponse <?php endif;
                ?>
                    {
    <?= $action['code'] ?>

    <?php if ($injectTemplate && $action['view'] == 'twig'): ?>

        return new HtmlResponse($this->template);
<?php elseif ($injectTemplate && $action['view'] == 'php'): ?>

        return new HtmlResponse($this->template);
<?php elseif ($action['view'] == 'json'): ?>

        return new JsonResponse([ "status"=>"ok" ]);
<?php elseif ($action['view'] == 'redirect'): ?>

        return new RedirectResponse(ROOT_URL.<?php var_export($action['redirect']);
                ?>);
<?php endif;
                ?>
    }

<?php endforeach;
                ?>
}
<?php
    $file = ob_get_clean();

                file_put_contents(ROOT_PATH.'../../../'.$fileName, $file);
                chmod(ROOT_PATH.'../../../'.$fileName, 0664);

    // Now, let's create the instance
    $controllerInstance = $moufManager->createInstance($namespace.'\\'.$controllerName);
                $controllerInstance->setName($instanceName);
                if ($injectLogger) {
                    if ($moufManager->has('psr.errorLogLogger')) {
                        $controllerInstance->getProperty('logger')->setValue($moufManager->getInstanceDescriptor('psr.errorLogLogger'));
                    }
                }
                if ($injectTemplate) {
                    if ($moufManager->has('bootstrapTemplate')) {
                        $controllerInstance->getProperty('template')->setValue($moufManager->getInstanceDescriptor('bootstrapTemplate'));
                    }
                    if ($moufManager->has('block.content')) {
                        $controllerInstance->getProperty('content')->setValue($moufManager->getInstanceDescriptor('block.content'));
                    }
                }
                if ($injectDaoFactory) {
                    if ($moufManager->has('daoFactory')) {
                        $controllerInstance->getProperty('daoFactory')->setValue($moufManager->getInstanceDescriptor('daoFactory'));
                    }
                }
                if ($injectTwig) {
                    if ($moufManager->has('twigEnvironment')) {
                        $controllerInstance->getProperty('twig')->setValue($moufManager->getInstanceDescriptor('twigEnvironment'));
                    }
                }

                $moufManager->rewriteMouf();

    // There is a new class, let's purge the cache
    $moufCache = new MoufCache();
                $moufCache->purgeAll();

    // TODO: purge cache
            }
        }

        if ($errors) {
            $exception = new SplashCreateControllerServiceException('Errors detected');
            $exception->setErrors($errors);
            throw $exception;
        }
    }

    /**
     * @param string $directory
     *
     * @return bool
     */
    private function createDirectory(string $directory) : bool
    {
        if (!file_exists($directory)) {
            // Let's create the directory:
            $old = umask(0);
            $result = @mkdir($directory, 0775, true);
            umask($old);

            return $result;
        }

        return true;
    }
}
