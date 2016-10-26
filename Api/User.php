<?php

/*
 * This file is part of the Formicula package.
 *
 * Copyright Formicula Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Formicula_Api_User extends Zikula_AbstractApi
{
    /**
     * getContact
     * reads a single contact by id
     *
     * @param cid int contact id
     * @param form int form id
     * @return array with contact information
     */
    public function getContact($args)
    {
        if (!isset($args['cid']) || !is_numeric($args['cid'])) {
            return LogUtil::registerArgsError();
        }
        if (!isset($args['form']) || !is_numeric($args['form'])) {
            $args['form'] = 0;
        }

        if (!SecurityUtil::checkPermission('ZikulaFormiculaModule::', $args['form'] . ':' . $args['cid'] . ':', ACCESS_COMMENT)) {
            return LogUtil::registerPermissionError();
        }

        $contact = DBUtil::selectObjectByID('formcontacts', $args['cid'], 'cid');

        return $contact;
    }

    /**
     * readValidContacts
     * reads the contact list and returns it as array.
     * This function filters out the entries the user is not allowed to see
     *
     * @param form int form id
     * @return array with contact information
     */
    public function readValidContacts($args)
    {
        $allContacts = ModUtil::apiFunc('ZikulaFormiculaModule', 'admin', 'readContacts');
        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if (false === $allContacts) {
            return LogUtil::registerError($this->__('Error! No contacts defined.'));
        }

        // Put items into result array.  Note that each item is checked
        // individually to ensure that the user is allowed access to it before it
        // is added to the results array
        $visibleContacts = [];
        foreach($allContacts as $contact) {
            if (SecurityUtil::checkPermission('ZikulaFormiculaModule::', $args['form'] . ':.*:', ACCESS_COMMENT)) {
                $visibleContacts[] = $contact;
            }
        }

        // Return the contacts
        return $visibleContacts;
    }

    /**
     * sendtoContact
     * sends the mail to the contact
     *
     * @param userdata array with user submitted data
     * @param contact array of contact information (single contact)
     * @param customFields array of custom fields information
     * @param form int form id
     * @param format   string email format, either 'plain' or 'html'
     * @return boolean
     */
    public function sendtoContact($args)
    {
        $userdata = $args['userdata'];
        $contact  = $args['contact'];
        $customFields   = $args['customFields'];
        $form     = DataUtil::formatForOS($args['form']);
        $format   = $args['format'];

        $mailerModule = 'ZikulaMailerModule';
        if (!ModUtil::available($mailerModule)) {
            // no mailer module - error!
            return false;
        }

        $sitename = System::getVar('sitename');
        $ipAddress = getenv('REMOTE_ADDR');

        $view = Zikula_View::getInstance('ZikulaFormiculaModule', false, null, true);
        $view->assign('hostName', gethostbyaddr($ipAddress))
             ->assign('ipAddress', $ipAddress)
             ->assign('form', $form)
             ->assign('contact', $contact)
             ->assign('userdata', $userdata)
             ->assign('siteName', $sitename);

        // attach all files we have got
        $attachments = [];
        $uploaddir = dirname(ZLOADER_PATH) . '/' . ModUtil::getVar('ZikulaFormiculaModule', 'uploadDirectory');

        foreach ($customFields as $k => $customField) {
            if (isset($customField['data']) && is_array($customField['data']))  {
                if ($customField['data']['name']) {
                    $attachments[] = $uploaddir . '/' . $customField['data']['name'];
                }
                $customFields[$k]['data'] = $customField['data']['name'];
            }
        }
        $view->assign('customFields', $customFields);

        switch ($format) {
            case 'html' :
                $body = $view->fetch('forms' . DIRECTORY_SEPARATOR . $form."_adminmail.tpl");
                $html = true;
                break;
            default:
                $body = $view->fetch('forms' . DIRECTORY_SEPARATOR . $form."_adminmail.txt");
                $html = false;
        }

        // subject of the emails can be determined from the form
        $adminsubject = !empty($userdata['adminsubject']) ? $userdata['adminsubject'] : $sitename." - ".$contact['name'];

        $mailArgs = [
            'fromname'    => $userdata['uname'],
            'toname'      => $contact['name'],
            'toaddress'   => $contact['email'],
            'subject'     => $adminsubject,
            'body'        => $body,
            'attachments' => $attachments,
            'html'        => $html
        ];

        if (true === ModUtil::getVar('ZikulaFormiculaModule', 'useContactsAsSender', true)) {
            $mailArgs['fromaddress'] = $userdata['uemail'];
        }

        $result = ModUtil::apiFunc($mailerModule, 'user', 'sendmessage', $mailArgs);

        if (true === ModUtil::getVar('ZikulaFormiculaModule', 'deleteUploadedFiles', true)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment) && is_file($attachment)) {
                    unlink($attachment);
                }
            }
        }

        return $result;
    }

    /**
     * sendtoUser
     * sends the confirmation mail to the user
     *
     * @param userdata array with user submitted data
     * @param contact  array with contact data
     * @param customFields   array of custom fields information
     * @param form     int form id
     * @param format   string email format, either 'plain' or 'html'
     * @return boolean
     */
    public function sendtoUser($args)
    {
        $userdata = $args['userdata'];
        $contact  = $args['contact'];
        $customFields   = $args['customFields'];
        $form     = DataUtil::formatForOS($args['form']);
        $format   = $args['format'];

        $mailerModule = 'ZikulaMailerModule';
        if (!ModUtil::available($mailerModule)) {
            // no mailer module - error!
            return false;
        }

        $sitename = System::getVar('sitename');
        $ipAddress = getenv('REMOTE_ADDR');

        $view = Zikula_View::getInstance('ZikulaFormiculaModule', false, null, true);
        $view->assign('hostName', gethostbyaddr($ipAddress))
             ->assign('ipAddress', $ipAddress)
             ->assign('form', $form)
             ->assign('contact', $contact)
             ->assign('userdata', $userdata)
             ->assign('siteName', $sitename)
             ->assign('customFields', ModUtil::apiFunc('ZikulaFormiculaModule', 'user', 'removeUploadInformation', ['customFields' => $customFields]));

        switch ($format) {
            case 'html' :
                $body = $view->fetch('forms' . DIRECTORY_SEPARATOR . $form . '_usermail.tpl');
                $html = true;
                break;
            default:
                $body = $view->fetch('forms' . DIRECTORY_SEPARATOR . $form . '_usermail.txt');
                $html = false;
        }

        // check for sender name
        if (!empty($contact['sname'])) {
            $fromname = $contact['sname'];
        } else {
            $fromname = $sitename . ' - ' . DataUtil::formatForDisplay($this->__('Contact form'));
        }
        // check for sender email
        if (!empty($contact['semail'])) {
            $frommail = $contact['semail'];
        } else {
            $frommail = $contact['email'];
        }

        // check for subject, can be in the form or in the contact
        if (!empty($contact['ssubject']) || !empty($userdata['usersubject'])) {
            $subject = !empty($userdata['usersubject']) ? $userdata['usersubject'] : $contact['ssubject'];
            // replace some placeholders
            // %s = sitename
            // %l = slogan
            // %u = site url
            // %c = contact name
            // %n<num> = user defined field name <num>
            // %d<num> = user defined field data <num>
            $subject = str_replace('%s', DataUtil::formatForDisplay($sitename), $subject);
            $subject = str_replace('%l', DataUtil::formatForDisplay(System::getVar('slogan')), $subject);
            $subject = str_replace('%u', System::getBaseUrl(), $subject);
            $subject = str_replace('%c', DataUtil::formatForDisplay($contact['sname']), $subject);
            foreach ($customFields as $num => $customField) {
                $subject = str_replace('%n' . $num, $customField['name'], $subject);
                $subject = str_replace('%d' . $num, $customField['data'], $subject);
            }
        } else {
            $subject = $sitename . ' - ' . $contact['name'];
        }

        $mailArgs = [
            'fromname'    => $fromname,
            'toname'      => $userdata['uname'],
            'toaddress'   => $userdata['uemail'],
            'subject'     => $subject,
            'body'        => $body,
            'html'        => $html
        ];

        if (true === ModUtil::getVar('ZikulaFormiculaModule', 'useContactsAsSender', true)) {
            $mailArgs['fromaddress'] = $frommail;
        }

        return ModUtil::apiFunc($mailerModule, 'user', 'sendmessage', $mailArgs);
    }

    /**
     * storeInDatabase
     * stores a form submit in the database
     *
     * @param userdata array with user submitted data
     * @param contact  array with contact data
     * @param customFields   array of custom fields information
     * @param form     int form id
     * @return boolean
     */
    public function storeInDatabase($args)
    {
        $userdata = $args['userdata'];
        $contact  = $args['contact'];
        $customFields = $args['customFields'];
        $form = DataUtil::formatForOS($args['form']);

        $formsubmit['form'] = $form;
        $formsubmit['cid'] = $contact['cid'];
        $formsubmit['name'] = $userdata['uname'];
        $formsubmit['email'] = $userdata['uemail'];
        $formsubmit['phone'] = $userdata['phone'];
        $formsubmit['company'] = $userdata['company'];
        $formsubmit['url'] = $userdata['url'];
        $formsubmit['location'] = $userdata['location'];
        $formsubmit['comment'] = $userdata['comment'];
        $customArray = [];
        foreach ($customFields as $customField) {
            $customArray[$customField['name']] = $customField['data'];
        }
        $formsubmit['customData'] = $customArray;
        $ipAddress = getenv('REMOTE_ADDR');
        $formsubmit['ipAddress'] = $ipAddress;
        $formsubmit['hostName'] = gethostbyaddr($ipAddress);

        if (!($obj = DBUtil::insertObject($formsubmit, 'formsubmits', 'sid'))) {
            return LogUtil::registerError($this->__f('Error! Could not store data submitted by form %s.', $form));
        }

        return true;
    }

    /**
     * checkArguments
     * checks if mandatory arguments are correct
     *
     * @param userdata array with user submitted data, we are interested in uemail, uname and comment here
     * @param customFields array with custom data
     * @param userformat string format of users email for relaxed checking if userformat=none
     * @return boolean
     */
    public function checkArguments($args)
    {
        $userdata = $args['userdata'];
        $customFields = $args['customFields'];
        $userformat = $args['userformat'];

        $ok = true;

        if ($userformat != 'none') {
            if (!isset($userdata['uemail']) || (System::varValidate($userdata['uemail'], 'email') == false)) {
                $ok = LogUtil::registerError($this->__('Error! No or incorrect email address supplied.'));
            }

            if (!isset($userdata['uname']) || empty($userdata['uname'])) {
                $ok = LogUtil::registerError($this->__('Error! No or invalid username given.'));
            }
        }

        foreach ($customFields as $field) {
            if (isset($field['mandatory']) && $field['mandatory']) {
                if (!is_array($field['data']) && (empty($field['data']))) {
                    $ok = LogUtil::registerError($this->__('Error! Mandatory field:' . DataUtil::formatForDisplay($field['name'])));
                }
                if (($field['upload'] == true) && ($field['data']['size'] == 0)) {
                    $ok = LogUtil::registerError($this->__('Error! Upload error.'));
                }
            }
        }

        return $ok;
    }

    /**
     * removeUploadInformation
     * replaces the information about uploaded files with the filename so that we can use it in the
     * templates
     *
     * @param customFields array of custom fields
     * @return cleaned custom array
     */
    public function removeUploadInformation($args)
    {
        $customFields = [];
        if (!isset($args['customFields']) || !is_array($args['customFields'])) {
            return $customFields;
        }

        $customFields = $args['customFields'];
        foreach ($customFields as $k => $customField) {
            if (isset($customField['upload']) && $customField['upload'] == true) {
                $customFields[$k]['data'] = $customFields[$k]['data']['name'];
            }
        }

        return $customFields;
    }
    
    /**
     * add ownContacts to the session information and return the id for generating the url
     *
     * @param ownContacts array of own contacts to replace with the standard. The array can contain the following values
     *    name the contact full name (required)
     *    sname the contact secure name wich will be send to the submitter (optional)
     *    email the contact email (required)
     *    semail the contact email wich will be send to the submiter (optional)
     *    ssubject the subject of the confirmation mail (optional)
     * @return id wich must be appended to the formicula url with the sheme owncontact=id
     */
    public function addSessionOwnContacts($args)
    {
        if (!ModUtil::apiFunc('ZikulaFormiculaModule', 'user', 'checkOwncontacts', $args)) {
            return false;
        }

        $ownContacts = SessionUtil::getVar('formiculaOwnContacts', []);
        $tmpid = array_search($args['ownContacts'], $ownContacts);
        if (!$tmpid) {
            $id = count($ownContacts);
            $ownContacts[] = $args['ownContacts'];
            SessionUtil::setVar('formiculaOwnContacts', $ownContacts);
        } else {
            $id = $tmpid;
        }

        return $id;
    }
    
    /**
     * validate ownContacts array
     *
     * @param ownContacts array of own contacts to replace with the standard. The array can contain the following values
     *    name the contact full name (required)
     *    sname the contact secure name wich will be send to the submitter (optional)
     *    email the contact email (required)
     *    semail the contact email wich will be send to the submiter (optional)
     *    ssubject the subject of the confirmation mail (optional)
     * @return true if validated, false if not
     */
    public function checkOwncontacts($args)
    {
        if (!isset($args['ownContacts'])) {
            return LogUtil::registerError($this->__('You must pass an ownContacts array!'));
        }

        foreach ($args['ownContacts'] as $item) {
            if (!isset($item['name'])) {
                return LogUtil::registerError($this->__('You must pass a name for each contact!'));
            }
            if (!isset($item['email']) || !filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                return LogUtil::registerError($this->__('You must pass a valid mail address for each contact!'));
            }
        }

        return true;
    }
}
