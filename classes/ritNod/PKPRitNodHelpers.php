<?php

/**
 * @file classes/ritNod/AgreementForm.php
 *
 * Copyright (c) 2023 Sasz Kolomon
 *
 * @class AgreementForm
 *
 * @ingroup user_form
 *
 * @brief Form to show user's Accession agreement information.
 */

namespace PKP\ritNod;

use PKP\security\Validation;
use APP\facades\Repo;
use PKP\core\Core;
use PKP\security\Role;
use APP\core\Request;
use APP\submission\Submission;
use PKP\db\DAORegistry;
use PKP\core\PKPApplication;
use APP\core\Application;
use APP\notification\NotificationManager;
use APP\notification\Notification;
use PKP\mail\mailables\EditorAssigned;
use PKP\log\SubmissionEmailLogEntry;
use PKP\log\event\PKPSubmissionEventLogEntry;
use APP\template\TemplateManager;
use APP\publication\Publication;
use PKP\doi\exceptions\DoiException;
use PKP\context\Context;
use PKP\config\Config;

class PKPRitNodHelpers {
    public static function prepareDogovir($user, $request) {
        $agreementText = file_get_contents('templates/ritNod/agreementText.html');
        $agreementText = preg_replace('/\<style\>[\S\s]*\<\/style\>/m', '', $agreementText);

        $agreementText = str_replace('{$path}', $request->getBaseUrl(), $agreementText);
        return $agreementText;
    }

    public static function testHelper()
    {
        return "<p>test Helper string</p>";
    }



    //from RitNod.php
    public static function loginFromRitNod($request)
    {
        // $notificationMgr = new NotificationManager();

        // $notificationMgr->createTrivialNotification(
        //     1,
        //     PKPNotification::NOTIFICATION_TYPE_SUCCESS,
        //     ['contents' => 'Trivial Error Message']
        // );
        // da();
        // return;


        $profileId = $_GET['profile_id'];
        // $lang = null;
        // if (isset($_GET['lang'])) {
        //     $lang = $_GET['lang'];
        // }
        // $lang = $_GET['lang'];
        $lang = $_GET['lang'] ?? null;

        $session = $request->getSession();
        $source = $session->getSessionVar('source');
        $session->setSessionVar('source', null);

        if (isset($profileId)) {
            if (Validation::isLoggedIn()) {
                Validation::logout();
            }
            $url = "https://opensi.nas.gov.ua/all/GetProfileByKey";

            $body = http_build_query(['token' => $profileId]);
            $opts = [
                'http' => [
                    'method' => "GET",
                    'header' =>
                    "Content-Type: application/x-www-form-urlencoded",
                    'content' => $body
                ]
            ];
            $context = stream_context_create($opts);

            $userInfo = file_get_contents($url, false, $context);

            // echo "<script>alert('<b>Alert</b> <p>Message here!</p>');</script>";
            // echo "<p>aaaaaaaaaaaa</p>";
            // echo "<div id='dialog' title='Basic dialog'><p>This is the default dialog .</p></div>";
            // echo "<script>$('#dialog').dialog();</script>";

            // $templateMgr = TemplateManager::getManager($request);
            // $templateMgr->assign([
            //     'isCategoriesEnabled' => $context->getData('submitWithCategories') && $categories->count(),
            //     'locales' => $orderedLocales,
            //     'pageComponent' => 'SubmissionWizardPage',
            //     'pageTitle' => __('submission.wizard.title'),
            //     'submission' => $submission,
            //     'submittingTo' => $this->getSubmittingTo($context, $submission, $sections, $categories),
            //     'reviewSteps' => $this->getReviewStepsForSmarty($steps),
            // ]);

            // $templateMgr->display('sasztest.tpl');
            // $templateMgr->assign([
            //     'pageTitle' => __('submission.submit.submissionComplete'),
            //     'pageWidth' => TemplateManager::PAGE_WIDTH_NARROW,
            //     'submission' => null,//$submission,
            //     'workflowUrl' => "dddd"//$this->getWorkflowUrl($submission, $request->getUser()),
            // ]);
            // $templateMgr->display('submission/complete.tpl');

            // $userInfo = null;
            // return;


            // if (!$userInfo) { //TODO: refacror Error messages
            //     echo "<p style='color:red;font-size:1.2rem;'>Error obtaining user profile / Помилка при отриманні даних користувача з РІТ НОД</p>";
            // } else if (strpos($userInfo, "error") !== false) {
            //     echo "<p style='color:red;font-size:1.2rem;'>Error / Помилка: " . $userInfo . "</p>";
            //     $userInfo = null;

            if (!$userInfo) {
                self::displayErrorModal($request, __('login.error.title'), __('login.error.description', ['error' => __('login.error.noresponce')]));
                return true;
            }
            $userProf = json_decode($userInfo);
            if (isset($userProf->Key) && $userProf->Key === "error") {
                self::displayErrorModal($request, __('login.error.title'), __('login.error.description', ['error' => $userProf->Value ?? $userInfo]));
                return true;
            }
            if (!self::checkUserProfile($request, $userProf)) {
                return true;
            }

            $reason = null;
            $password = Validation::generatePassword();
            [$username, $userId, $notVerified] = self::addOrUpdateUserProf($userProf, $password);

            if (isset($username)) {
                self::assignUserRoles($request, $userId);

                // Associate the new user with the existing session
                // $sessionManager = SessionManager::getManager();
                // $session = $sessionManager->getUserSession();
                $session->setSessionVar('username', $username);
                $session->setSessionVar('profileId', $profileId);

                // $user = Repo::user()->get($userId);
                // $user->setPassword(Validation::encryptCredentials($username, "password123"));
                // $user->setMustChangePassword(0);
                // $user->setDisabled(true);
                // $user->setDisabledReason('Not validated');

                //!!!!!! Ознака Договору приєднання !!!!!!!!!!
                // $user->setDateValidated(Core::getCurrentDate()); 

                // Repo::user()->edit($user);

                if (isset($lang)) {
                    self::setUserLocale($request, $lang);
                }

                ///////
                //Договір приєднання
                if ($notVerified) {
                    //reload page once
                    if (!isset($_GET['r'])) { //needed to init server environment (don't know why so)
                        $request->redirect(null, 'index', null, null, ['profile_id' => $profileId, 'r' => '1']);
                    }
                    $userAgreedKey = self::getDogovirKey($request); //'k' . md5(Application::get()->getUUID());
                    $templateMgr = TemplateManager::getManager($request);

                    $agreementText = file_get_contents('templates/ritNod/agreementText.html');
                    // /\<style\>[\S\s]*\<\/style\>/gm
                    $agreementText = preg_replace('/\<style\>[\S\s]*\<\/style\>/m', '', $agreementText);

                    $agreementText = str_replace('{$path}', $request->getBaseUrl(), $agreementText);

                    $templateMgr->assign([
                        'agreementText' => $agreementText,
                        'name' => $userProf->prizvische_ua . ' ' . $userProf->imya_ua . ' ' . $userProf->pobatkovi_ua,
                        'name_en' => $userProf->imya_en . ' ' . $userProf->prizvische_en,
                        'path' => $request->getBaseUrl()
                    ]);
                    // echo $userAgreedKey;
                    $request->setCookieVar("dogovir", $userAgreedKey, time() + 60 * 60);
                    $templateMgr->display('/ritNod/agreement.tpl');
                    return false;
                    // $request->redirect(null,'login','signOut');
                }


                ///////

                Validation::login($username, $password, $reason, true);
                // if (isset($lang)) {
                //     self::setUserLocale($request, $lang);
                // }

                if ($source) {
                    $request->redirectUrl($source);
                } else {
                    $request->redirect(null, "index");
                }
            }
        }
        return true;
    }

    public static function getDogovirKey($request)
    {
        $session = $request->getSession();
        $username = $session->getSessionVar('username');
        if (!$username) {
            return null;
        }
        $user = Repo::user()->getByUsername($username, true);
        if (!$user || !$user->getPassword()) {
            return null;
        }
        return 'k' . md5($user->getPassword() . date('yd'));
    }

    public static function verifyUser($request)
    {
        // if(self::getDogovirKey($request) !== $key) {
        //     $request->redirect(null, 'login', 'signOut');
        // }
        $session = $request->getSession();
        $username = $session->getSessionVar('username');
        if (!$username) {
            $request->redirect(null, 'login', 'signOut');
        }
        $user = Repo::user()->getByUsername($username, true);
        if (!$user || !$user->getPassword()) {
            $request->redirect(null, 'login', 'signOut');
        }

        $user->setDateValidated(Core::getCurrentDate()); //Ознака Договору приєднання 
        $password  = Validation::generatePassword();
        $user->setPassword(Validation::encryptCredentials($username, $password));
        $user->setMustChangePassword(0);

        Repo::user()->edit($user);

        $reason = null;
        Validation::login($username, $password, $reason, true);
        $request->redirect(null, "index");
    }

    public static function checkUserProfile($request, $userProf)
    {
        $errors = '';
        $checkPoints = ['imya_ua', 'prizvische_ua', 'imya_en', 'prizvische_en', 'agreement_publ']; //'ORCID'?
        foreach ($checkPoints as $point) {
            if (!isset($userProf->$point) || !$userProf->$point) {
                $errors .= '<li>' . __('profile.error.' . $point) . '</li>';
            }
        }

        if ($errors) {
            $message = __('profile.error.description', ['errors' => '<ul>' . $errors . '</ul>']);
            self::displayErrorModal($request, __('profile.error.title'),  $message);
            return false;
        }
        return true;
    }

    public static function assignUserRoles($request, $userId, $moderator = false)
    {
        if ($request->getContext() /*&& isset($user) */ /*&& isset($_GET['profile_id'])*/) {
            $contextId = $request->getContext()->getId();

            if ($moderator) {
                $defaultGroup = Repo::userGroup()->getByRoleIds([Role::ROLE_ID_SUB_EDITOR], $contextId, true)->first();
                if ($defaultGroup && !Repo::userGroup()->userInGroup($userId, $defaultGroup->getId())) {
                    Repo::userGroup()->assignUserToGroup($userId, $defaultGroup->getId());
                }
            } else {
                $defaultGroup = Repo::userGroup()->getByRoleIds([Role::ROLE_ID_READER], $contextId, true)->first();
                if ($defaultGroup && !Repo::userGroup()->userInGroup($userId, $defaultGroup->getId())) {
                    Repo::userGroup()->assignUserToGroup($userId, $defaultGroup->getId());
                }
                $defaultGroup = Repo::userGroup()->getByRoleIds([Role::ROLE_ID_AUTHOR], $contextId, true)->first();
                if ($defaultGroup && !Repo::userGroup()->userInGroup($userId, $defaultGroup->getId())) {
                    Repo::userGroup()->assignUserToGroup($userId, $defaultGroup->getId());
                }
            }
        }
    }

    public static function setUserLocale($request, $lang) //"en" | "ua"
    {
        $session = $request->getSession();
        $session->setSessionVar('currentLocale', $lang == "en" ? "en" : "uk");

        // $request->redirect(null, "index");
    }

    public static function addOrUpdateUserProf($userProf, $password = null)
    {
        $email = $userProf->email;
        $username = explode("@", $email)[0];
        $firstName = $userProf->imya_ua;
        $lastName = $userProf->prizvische_ua;
        $pobatkovi = $userProf->pobatkovi_ua;
        $pobatkoviEn = $userProf->pobatkovi_en;
        $firstNameEn = $userProf->imya_en;
        $lastNameEn = $userProf->prizvische_en;
        $affiliation = $userProf->full_name_inst;
        $affiliationEn = $userProf->full_name_inst_en;
        $orcid = $userProf->ORCID;
        if ($orcid && !str_contains(strtolower($orcid), 'orcid.org')) {
            $orcid = 'https://orcid.org/' . $orcid;
        }

        if (!isset($password)) {
            $password  = Validation::generatePassword();
        }

        $user = Repo::user()->getByUsername($username, true);

        $newUser = true;
        $notVerified = true;
        if (isset($user)) {
            $newUser = false;
            if ($user->getDateValidated()) {
                $notVerified = false;
            }
        }

        // New user
        if ($newUser) {


            // $aggrModal = new ConfirmationModal(
            //     "You need to sign an aggreement!",
            //     "New User here",
            //     'modal_information',
            //     null,
            //     '',
            //     false
            // );
            // $aggrModal->sho






            $user = Repo::user()->newDataObject();

            $user->setUsername($username);
            $user->setDateRegistered(Core::getCurrentDate());
            $user->setInlineHelp(1); // default new users to having inline help visible.
        }

        $user->setEmail($email);

        // The multilingual user data (givenName, familyName and affiliation) will be saved
        // in the current UI locale and copied in the site's primary locale too

        $user->setCountry("UA"); //TODO !!!

        $ual = "uk";
        $enl = "en";
        $user->setGivenName($firstName, $ual);
        $user->setFamilyName($lastName, $ual);
        $user->setAffiliation($affiliation, $ual);

        $user->setData("poBatkovi", $pobatkovi, $ual);

        if ($firstNameEn && $lastNameEn) {
            $user->setGivenName($firstNameEn, $enl);
            $user->setFamilyName($lastNameEn, $enl);
            $user->setData("poBatkovi", $pobatkoviEn, $enl);
        }
        if ($affiliationEn) {
            $user->setAffiliation($affiliationEn, $enl);
        }
        $user->setOrcid($orcid);

        $user->setPassword(Validation::encryptCredentials($username, $password));
        $user->setMustChangePassword(0);

        if ($newUser) {
            Repo::user()->add($user);
        } else {
            Repo::user()->edit($user);
        }

        $userId = $user->getId();
        if (!$userId) {
            return [null, null];
        }

        // self::assignUserRoles($request, $userId);

        return [$username, $userId, $notVerified];
    }

    public static function assignModerator(Request $request, Submission $submission): bool
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        /** @var StageAssignmentDAO $stageAssignmentDao */
        $contextId = $request->getContext()->getId();
        $defaultModerGroup = Repo::userGroup()->getByRoleIds([Role::ROLE_ID_SUB_EDITOR], $contextId, true)->first();
        $userGroupId = $defaultModerGroup->getId();

        // $sessionManager = SessionManager::getManager();
        // $session = $sessionManager->getUserSession();
        $session = $request->getSession();
        $profileId = $session->getSessionVar('profileId');
        $assignmentId = $session->getSessionVar('assignmentId');

        if ($assignmentId) { //avoid duplicate moderator assignment
            $stageAssignment = $stageAssignmentDao->getById($assignmentId);
            if (
                $stageAssignment
                && $stageAssignment->getSubmissionId() == $submission->getId()
                && $stageAssignment->getUserGroupId() == $userGroupId
            ) {
                return true; //already assigned
            }
        }

        //TODO: make changes like in RitNod.php here!!!!!

        //Look up Moderators in RIT NOD
        $url = "https://opensi.nas.gov.ua/all/GetCuratorsPreprint";

        $body = http_build_query(['token' => $profileId]);
        $opts = [
            'http' => [
                // 'method'=>"GET",
                'content' => $body
            ]
        ];
        $context = stream_context_create($opts);

        $moderResp = file_get_contents($url, false, $context);

        if (!$moderResp || strpos($moderResp, "error") !== false) {
            return false;
        }

        $moderArr = json_decode($moderResp);
        if (gettype($moderArr) !== 'array') {
            return false;
        }

        $recommendOnly = false;
        $canChangeMetadata = false;

        $notificationManager = new NotificationManager();
        $logDao = DAORegistry::getDAO('SubmissionEmailLogDAO');

        // foreach ($moderArr as $moderInfo) {
        $moderCnt = count($moderArr);
        if ($moderCnt > 0) {
            $moderInfo = $moderArr[rand(0, $moderCnt - 1)]; //pick random moderator
            [, $userId] = self::addOrUpdateUserProf($moderInfo);
            if (isset($userId)) {
                self::assignUserRoles($request, $userId, true);
                //add user to submission as moderator
                $stageAssignment = $stageAssignmentDao->build($submission->getId(), $userGroupId, $userId, $recommendOnly, $canChangeMetadata);
                $session->setSessionVar('assignmentId', $stageAssignment->getId());

                //nofify
                $user = Repo::user()->get($userId);
                // $notificationManager->createTrivialNotification($userId, PKPNotification::NOTIFICATION_TYPE_SUCCESS, ['contents' => __('notification.addedStageParticipant')]);

                // Send notification
                $notification = $notificationManager->createNotification(
                    $request,
                    $userId,
                    Notification::NOTIFICATION_TYPE_EDITOR_ASSIGN,
                    $contextId,
                    Application::ASSOC_TYPE_SUBMISSION,
                    $submission->getId(),
                    Notification::NOTIFICATION_LEVEL_TASK
                );

                // Send email
                $emailTemplate = Repo::emailTemplate()->getByKey($contextId, EditorAssigned::getEmailTemplateKey());
                $mailable = new EditorAssigned($request->getContext(), $submission);

                // The template may not exist, see pkp/pkp-lib#9217; FIXME remove after #9202 is resolved
                if (!$emailTemplate) {
                    $emailTemplate = Repo::emailTemplate()->getByKey($contextId, 'NOTIFICATION');
                    $request = Application::get()->getRequest();
                    $mailable->addData([
                        'notificationContents' => $notificationManager->getNotificationContents($request, $notification),
                        'notificationUrl' => $notificationManager->getNotificationUrl($request, $notification),
                    ]);
                }

                $mailable
                    ->from($request->getContext()->getData('contactEmail'), $request->getContext()->getData('contactName'))
                    ->subject($emailTemplate->getLocalizedData('subject'))
                    ->body($emailTemplate->getLocalizedData('body'))
                    ->recipients([$user]);

                Mail::send($mailable);

                // Log email
                $logDao->logMailable(
                    SubmissionEmailLogEntry::SUBMISSION_EMAIL_EDITOR_ASSIGN,
                    $mailable,
                    $submission
                );

                // Log addition.
                $assignedUser = Repo::user()->get($userId, true);
                $eventLog = Repo::eventLog()->newDataObject([
                    'assocType' => PKPApplication::ASSOC_TYPE_SUBMISSION,
                    'assocId' => $submission->getId(),
                    'eventType' => PKPSubmissionEventLogEntry::SUBMISSION_LOG_ADD_PARTICIPANT,
                    'userId' => Validation::loggedInAs() ?? $user->getId(),
                    'message' => 'submission.event.participantAdded',
                    'isTranslated' => false,
                    'dateLogged' => Core::getCurrentDate(),
                    'userFullName' => $assignedUser->getFullName(),
                    'username' => $assignedUser->getUsername(),
                    'userGroupName' => $defaultModerGroup->getData('name')
                ]);
                Repo::eventLog()->add($eventLog);
            }
        }
        else {
            return false;
        }
        return true;
    }

    public static function displayErrorModal($request, $title, $message)
    {
        $templateMgr = TemplateManager::getManager($request);

        $templateMgr->assign([
            'title' => $title,
            'message' => $message
        ]);
        $templateMgr->display('/ritNod/errorModal.tpl');
    }

    public static function registerDoiOnDataCite(Context $context, Publication $publication, $publicationUrl)
    {
        function decodeString($strEnc)
        {
            return json_decode('"' . $strEnc . '"');
        }

        $doiCreationFailures = [];
        $doiUser = Config::getVar('doi', 'username');
        $doiPassword = Config::getVar('doi', 'password');
        $doiUrl = Config::getVar('doi', 'url');
        if (!$doiUser || !$doiPassword || !$doiUrl) {
            $doiCreationFailures[] = new DoiException('doi.dataCiteNoPassword');
            return $doiCreationFailures;
        }
        $doiPrefix = $context->getData(Context::SETTING_DOI_PREFIX);
        if (empty($doiPrefix)) {
            $doiCreationFailures[] = new DoiException('doi.exceptions.missingPrefix');
            return $doiCreationFailures;
        }

        if (empty($publication->getData('doiId'))) {
            $publLoc = $publication->getData('locale');
            $otherLoc = $publLoc == 'en' ? 'uk' : 'en';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $doiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/vnd.api+json"
            ));
            curl_setopt($ch, CURLOPT_USERPWD, $doiUser . ':' . $doiPassword);

            $authorsArray = [];
            foreach ($publication->getData('authors') as $author) {
                $nameEn = $author->getData('givenName', 'en');
                // $nameUk = $author->getData('givenName','uk');
                $lastNameEn = $author->getData('familyName', 'en');
                // $lastNameUk = $author->getData('familyName','uk');
                // $fullNameEn = $author->getData('preferredPublicName', 'en') ?? $lastNameEn . ', ' . $nameEn;
                // $fullNameUk = $author->getData('preferredPublicName','uk') ?? $lastNameUk . ', ' . $nameUk;
                $fullNameEn = $lastNameEn . ', ' . $nameEn;
                $authorData = [
                    "name" => $fullNameEn,
                    "nameType" => "Personal",
                    "givenName" => $nameEn,
                    "familyName" => $lastNameEn,
                    "affiliation" => [
                        [
                            "name" => $author->getData('affiliation', 'en')
                        ]
                    ],
                ];
                $orcid = $author->getOrcid();
                if (isset($orcid) && !empty($orcid)) {
                    $authorData["nameIdentifiers"] = [
                        [
                            "schemeUri" => "https://orcid.org",
                            "nameIdentifier" => $orcid,
                            "nameIdentifierScheme" => "ORCID"
                        ]
                    ];
                }
                $authorsArray[] = $authorData;
            }

            $titleArray = [
                [
                    "lang" => $publLoc,
                    "title" => decodeString(strip_tags($publication->getData('title', $publLoc))),
                ],
                [
                    "lang" => $otherLoc,
                    "title" => decodeString(strip_tags($publication->getData('title', $otherLoc))),
                    "titleType" => "TranslatedTitle"
                ]
            ];
            $descriptionArray = [
                [
                    "lang" => "en",
                    "description" => decodeString(strip_tags($publication->getData('abstract', 'en'))),
                    "descriptionType" => "Abstract"
                ],
                [
                    "lang" => "uk",
                    "description" => decodeString(strip_tags($publication->getData('abstract', 'uk'))),
                    "descriptionType" => "Abstract"
                ]
            ];

            $datePublished = $publication->getData('datePublished');
            $year = $publication->getData('copyrightYear');

            $data = [
                "data" => [
                    "type" => "dois",
                    "attributes" => [
                        "prefix" => $doiPrefix,
                        "event" => "publish",

                        "creators" => $authorsArray,
                        "titles" => $titleArray,
                        "publisher" => "Arxiv Academy",
                        "publicationYear" => $year,
                        "descriptions" => $descriptionArray,
                        "rightsList" => [
                            [
                                "rightsUri" => decodeString($publication->getData('licenseUrl'))
                            ]
                        ],
                        "url" => decodeString($publicationUrl),
                        "language" => $publLoc,
                        "types" => [
                            "resourceTypeGeneral" => "Preprint"
                        ],
                        "dates" => [
                            "date" => $datePublished,
                            "dateType" => "Issued"
                        ]
                    ]
                ]
            ];

            $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $res = curl_exec($ch);
            $response = json_decode($res);
            curl_close($ch);

            if (isset($response->errors)) {
                $doiCreationFailures[] = new DoiException(
                    'doi.dataCiteError',
                    $publication->getData('title', $publLoc),
                    json_encode(
                        $response->errors,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    )
                );
            } else {
                $doiSuffix = $response->data->attributes->suffix;
                if (!isset($doiSuffix) || empty($doiSuffix)) {
                    $doiCreationFailures[] = new DoiException(
                        'doi.dataCiteError',
                        $publication->getData('title', $publLoc)
                    );
                    return $doiCreationFailures;
                }
                try {
                    $completedDoi = $doiPrefix . '/' . $doiSuffix;

                    $doiDataParams = [
                        'doi' => $completedDoi,
                        'contextId' => $context->getId()
                    ];

                    $doi = Repo::doi()->newDataObject($doiDataParams);
                    $doiId =  Repo::doi()->add($doi);

                    Repo::doi()->markRegistered($doiId);
                    Repo::publication()->edit($publication, ['doiId' => $doiId]);
                } catch (DoiException $exception) {
                    $doiCreationFailures[] = $exception;
                }
            }
        }
        return $doiCreationFailures;
    }

}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\ritNod\PKPRitNodHelpers', '\PKPRitNodHelpers');
}
