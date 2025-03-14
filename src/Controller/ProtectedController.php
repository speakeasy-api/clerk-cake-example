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
use Cake\Event\EventInterface;

/**
 * Protected Controller for secured endpoints
 * All methods in this controller require authentication
 */
class ProtectedController extends AppController
{
    /**
     * Initialize controller settings
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['data']);
    }

    public function clerkJwt()
    {
        $identity = $this->Authentication->getIdentity(); 
        $this->set('data', ['userId' =>  $identity ? $identity->getIdentifier() : null]);
    }

    public function getGated()
    {  
        $this->set('data', ['foo' => 'bar']);
    }
}
