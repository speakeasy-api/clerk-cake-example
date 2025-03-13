<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class ProtectedController extends AppController
{
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    public function clerkJwt()
    {
        $this->viewBuilder()->setClassName('Json');

        $authPayload = $this->getRequest()->getSession()->read('Auth.User');
        if ($authPayload !== null) {
            $this->set('data', ['userId' => $authPayload->sub]);
        } else {
            $this->set('data', ['userId' => null]);
        }
        $this->viewBuilder()->setOption('serialize', ['data']);
    }

    public function getGated()
    {
        $this->viewBuilder()->setClassName('Json');

        $authPayload = $this->getRequest()->getSession()->read('Auth.User');
        if ($authPayload != null && $authPayload->sub) {
            $this->set('data', ['foo' => 'bar']);
        } else {
            throw new ForbiddenException('You are not authorized to access this resource');
        }

        $this->viewBuilder()->setOption('serialize', ['data']);
    }

}
