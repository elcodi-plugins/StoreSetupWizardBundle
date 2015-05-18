<?php

/*
 * This file is part of the Elcodi package.
 *
 * Copyright (c) 2014-2015 Elcodi.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @author Aldo Chiecchia <zimage@tiscali.it>
 * @author Elcodi Team <tech@elcodi.com>
 */

namespace Elcodi\Plugin\StoreSetupWizardBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Elcodi\Component\Configuration\Services\ConfigurationManager;
use Elcodi\Component\Plugin\Entity\Plugin;
use Elcodi\Plugin\StoreSetupWizardBundle\Services\WizardStatus;

/**
 * Class DisableUnderConstructionEventListener
 */
class DisableUnderConstructionEventListener
{
    /**
     * @var string
     *
     * Store enabled
     */
    const STORE_UNDER_CONSTRUCTION_CONFIG_KEY = 'store.under_construction';

    /**
     * @var Plugin
     *
     * Plugin
     */
    protected $plugin;

    /**
     * @var UrlGeneratorInterface
     *
     * An url generator
     */
    protected $urlGenerator;

    /**
     * @var WizardStatus
     *
     * A wizard status service
     */
    protected $wizardStatus;

    /**
     * @var ConfigurationManager
     *
     * A configuration manager
     */
    protected $configurationManager;

    /**
     * @var TranslatorInterface
     *
     * A translator
     */
    protected $translator;

    /**
     * Builds a new class
     *
     * @param TranslatorInterface   $translator           A translator
     * @param UrlGeneratorInterface $urlGenerator         An url generator
     * @param WizardStatus          $wizardStatus         A wizard status
     *                                                    service
     * @param ConfigurationManager  $configurationManager A configuration
     *                                                    manager
     */
    public function __construct(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        WizardStatus $wizardStatus,
        ConfigurationManager $configurationManager
    ) {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->wizardStatus = $wizardStatus;
        $this->configurationManager = $configurationManager;
    }

    /**
     * Set plugin
     *
     * @param Plugin $plugin Plugin
     *
     * @return $this Self object
     */
    public function setPlugin(Plugin $plugin)
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Handles the event avoiding the user to enable the store if the wizard is
     * not finished
     *
     * @param GetResponseEvent $event The response event
     */
    public function handle(GetResponseEvent $event)
    {
        if (
            $this->plugin->isEnabled() &&
            !$this->wizardStatus->isWizardFinished()
        ) {
            $request = $event->getRequest();
            $currentRoute = $this->getCurrentRequestRoute($request);

            if (
                'admin_configuration_update' == $currentRoute &&
                self::STORE_UNDER_CONSTRUCTION_CONFIG_KEY
                == $request->get('name')

            ) {
                $this->configurationManager->set(
                    self::STORE_UNDER_CONSTRUCTION_CONFIG_KEY,
                    'on'
                );

                $event->setResponse(
                    new JsonResponse(
                        [
                            'status'   => 403,
                            'response' => [
                                $this
                                    ->translator
                                    ->trans('wizard.first_finish_wizard'),
                            ],
                        ],
                        403
                    )
                );
            }
        }
    }

    /**
     * Gets the current request route
     *
     * @param Request $request The current request
     *
     * @return string
     */
    protected function getCurrentRequestRoute(Request $request)
    {
        return $request->get('_route');
    }
}
