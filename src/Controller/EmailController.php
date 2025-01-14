<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace PassboltSeleniumApi\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\HttpException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;

class EmailController extends AppController
{
    /**
     * @inheritDoc
     */
    public function beforeFilter(EventInterface $event)
    {
        if (Configure::read('debug') && Configure::read('passbolt.selenium.active')) {
            $this->Authentication->allowUnauthenticated(['showLastEmail']);
        } else {
            throw new NotFoundException();
        };

        return parent::beforeFilter($event);
    }

    /**
     * Show last email sent to a particular user.
     * Make sure you send email address URL encoded
     *
     * @param string $username the email of the user
     * @throws HttpException
     * @return void
     */
    public function showLastEmail($username)
    {
        // Initiate table to load avatar size configuration
        TableRegistry::getTableLocator()->get('Avatars');

        // If username is not an email, throw an error
        if (!Validation::email($username)) {
            throw new HttpException(__('Username not correct'));
        }
        // If username doesn't exist, we return an error.
        $Users = TableRegistry::getTableLocator()->get('Users');
        $u = $Users->find('all')
            ->where(['username' => $username])
            ->first();

        // If not found, we return an error.
        if (empty($u)) {
            throw new HttpException(__('The username does not exist.'));
        }
        $EmailQueue = TableRegistry::getTableLocator()->get('EmailQueue.EmailQueue');
        $email = $EmailQueue->find('all')
            ->where(['email' => $username])
            ->order(['created' => 'DESC'])
            ->first();
        if (empty($email)) {
            throw new HttpException(__('No email was sent to this user.'));
        }

        // Get template, template vars, subject and format
        $format = $email->format;
        $this->set('title', $email->subject);
        $this->set('body', $email->template_vars['body']);

        $this->viewBuilder()
            ->setLayout('default')
            ->setLayoutPath("email/$format")
            ->setTemplate($email->template)
            ->setTemplatePath("email/$format");
    }
}
