<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Controller\Frontend;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ajax Localization Controller
 */
class AjaxLocalizationController extends Controller
{
    /**
     * @Route("/set-current-localization", name="oro_frontend_localization_frontend_set_current_localization")
     * @Method({"POST"})
     * @CsrfProtection()
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setCurrentLocalizationAction(Request $request)
    {
        $localization = $this->get('oro_locale.manager.localization')
            ->getLocalization($request->get('localization'), false);
        $userLocalizationManager = $this->get('oro_frontend_localization.manager.user_localization');

        $result = false;
        if (array_key_exists($localization->getId(), $userLocalizationManager->getEnabledLocalizations())) {
            $userLocalizationManager->setCurrentLocalization($localization);
            $result = true;
        }

        return new JsonResponse(['success' => $result]);
    }
}
