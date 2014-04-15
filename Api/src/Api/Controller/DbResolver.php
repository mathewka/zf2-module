<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Api\Controller;

use Zend\Stdlib\ErrorHandler;
use Zend\Authentication\Adapter\Http\ResolverInterface;
use Zend\Authentication\Result as AuthResult;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
/**
 * HTTP Authentication File Resolver
 */
class DbResolver implements ResolverInterface
{    
    protected $adapter;
	function __construct(AuthAdapter $adapter){
	    $this->adapter = $adapter;
	}
	/**
     * Resolve credentials
     *
     * Only the first matching username/realm combination in the file is
     * returned. If the file contains credentials for Digest authentication,
     * the returned string is the password hash, or h(a1) from RFC 2617. The
     * returned string is the plain-text password for Basic authentication.
     *
     * The expected format of the file is:
     *   username:realm:sharedSecret
     *
     * That is, each line consists of the user's username, the applicable
     * authentication realm, and the password or hash, each delimited by
     * colons.
     *
     * @param  string $username Username
     * @param  string $realm    Authentication Realm
     * @return string|false User's shared secret, if the user is found in the
     *         realm, false otherwise.
     * @throws Exception\ExceptionInterface
     */
    public function resolve($username, $realm, $password = null)
    {
        if (empty($username)) {
            throw new Exception\InvalidArgumentException('Username is required');
        } elseif (!ctype_print($username) || strpos($username, ':') !== false) {
            throw new Exception\InvalidArgumentException('Username must consist only of printable characters, '
                                                              . 'excluding the colon');
        }
        if (empty($realm)) {
            throw new Exception\InvalidArgumentException('Realm is required');
        } elseif (!ctype_print($realm) || strpos($realm, ':') !== false) {
            throw new Exception\InvalidArgumentException('Realm must consist only of printable characters, '
                                                              . 'excluding the colon.');
        }

       //looking for matching credentials
        ErrorHandler::start(E_WARNING);		
        $this->adapter->setIdentity($username)->setCredential($password);
        $select = $this->adapter->getDbSelect();
        $result = $this->adapter->authenticate();
        return $result;
    }
}
