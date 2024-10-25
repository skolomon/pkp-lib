<?php

/**
 * @file classes/user/form/AgreementForm.php
 *
 * Copyright (c) 2023 Sasz Kolomon
 *
 * @class AgreementForm
 *
 * @ingroup user_form
 *
 * @brief Form to show user's Accession agreement information.
 */

namespace PKP\user\form;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\user\User;
use PKP\ritNod\PKPRitNodHelpers;

// import('classes.ritNod.ritNod');

class AgreementForm extends BaseProfileForm
{
    /**
     * Constructor.
     *
     * @param User $user
     */
    public function __construct($user)
    {
        parent::__construct('user/agreementForm.tpl', $user);

        // the users register for the site, thus
        // the site primary locale is the required default locale
        $site = Application::get()->getRequest()->getSite();
        $this->addSupportedFormLocale($site->getPrimaryLocale());
    }

    /**
     * @copydoc BaseProfileForm::fetch
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        // $templateMgr->initialize($request);

        $user = $this->getUser();

        // $agreementText = file_get_contents('templates/ritNod/agreementText.html');
        // $agreementText = preg_replace('/\<style\>[\S\s]*\<\/style\>/m', '', $agreementText);

        // $agreementText = str_replace('{$path}', $request->getBaseUrl(), $agreementText);

        $agreementText = PKPRitNodHelpers::prepareDogovir($user, $request);

        // $dateFormat = $request->getContext()->getLocalizedDateFormatLong();
        // echo $user->getDateValidated();
        $templateMgr->assign([
            // 'username' => $user->getUsername(),
            'agreementText' => $agreementText,
            'date' => $user->getDateValidated(), //date_create($user->getDateValidated()),//->format($dateFormat),
            // 'dateFormat' => $request->getContext()->getLocalizedDateFormatLong(),
            // 'dateFormat' => $dateFormat,
            // 'testDate' => date("d M Y", date_create("2022-10-12")->getTimestamp())
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc BaseProfileForm::initData()
     */
    public function initData()
    {
        // $user = $this->getUser();

        // $this->_data = [
        //     'givenName' => $user->getGivenName(null),
        //     'familyName' => $user->getFamilyName(null),
        //     'poBatkovi' => $user->getData('poBatkovi', null),
        //     'preferredPublicName' => $user->getPreferredPublicName(null),
        // ];
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        // parent::readInputData();

        // $this->readUserVars([
        //     'givenName', 'familyName', 'poBatkovi', 'preferredPublicName',
        // ]);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        // $request = Application::get()->getRequest();
        // $user = $request->getUser();

        // $user->setGivenName($this->getData('givenName'), null);
        // $user->setFamilyName($this->getData('familyName'), null);
        // $user->setData("poBatkovi", $this->getData('poBatkovi'), null);
        // $user->setPreferredPublicName($this->getData('preferredPublicName'), null);

        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\user\form\AgreementForm', '\AgreementForm');
}
