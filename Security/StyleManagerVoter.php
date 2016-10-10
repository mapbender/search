<?php

namespace Mapbender\SearchBundle\Security;

use FOM\UserBundle\Entity\User;
use Mapbender\CoreBundle\Component\SecurityContext;
use Mapbender\SearchBundle\Component\StyleManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class StyleManagerVoter
 *
 * @package Mapbender\SearchBundle\Security
 * @author  Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 */
class StyleManagerVoter implements VoterInterface
{


    static $VALID_ATTRIBUTES = array(
        self::CREATE,
        self::GET,
        self::VIEW,
        self::REMOVE

    );
    const GET = 'get';
    const CREATE     = 'create';
    const VIEW       = 'view';
    const REMOVE     = 'remove';
    const ROLE_ADMIN = "ROLE_ADMIN";

    /**
     * MetadataVoter constructor.
     *
     * @param AccessDecisionManager $securityContext
     */
    public function __construct()
    {

    }


    /**
     * Checks if the voter supports the given attribute.
     *
     * @param mixed $attribute An attribute (usually the attribute name string)
     *
     * @return bool true if this Voter supports the attribute, false otherwise
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, static::$VALID_ATTRIBUTES);
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param string $class A class name
     *
     * @return bool true if this Voter can process the class
     */
    public function supportsClass($class)
    {
        return $class == StyleManager::class;
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface    $token        A TokenInterface instance
     * @param StyleManager|null $styleManager The object to secure
     * @param array             $attributes   An array of attributes associated with the method being invoked
     *
     * @return int either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $styleManager, array $attributes)
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $styleManager->isPublic = $styleManager->getUserId() == SecurityContext::USER_ANONYMOUS_ID;

        switch ($attributes) {
            case self::GET:
                return $this->canGet($styleManager, $user, $token);
            case self::UPDATE:
                return $this->canEdit($styleManager, $user, $token);
            case self::VIEW:
                return $this->canView($styleManager, $user, $token);
            case self::CREATE:
                return $this->canCreate($styleManager, $user, $token);
        }

    }

    /**
     * @param StyleManager   $styleManager
     * @param User           $user
     * @param TokenInterface $token
     */
    private function canView(StyleManager $styleManager, $user, $token)
    {
        $roles   = $user->getRoles();
        $isAdmin = in_array(self::ROLE_ADMIN, $roles);

        return $isAdmin ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param StyleManager   $styleManager
     * @param User           $user
     * @param TokenInterface $token
     */
    private function canEdit(StyleManager $styleManager, $user, $token)
    {
        $roles   = $user->getRoles();
        $isAdmin = in_array(self::ROLE_ADMIN, $roles);

        return $isAdmin || $styleManager->isPublic ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param StyleManager   $styleManager
     * @param User           $user
     * @param TokenInterface $token
     */
    private function canGet(StyleManager $styleManager, $user, $token)
    {

        $roles   = $user->getRoles();
        $isAdmin = in_array(self::ROLE_ADMIN, $roles);

        return $isAdmin || $styleManager->isPublic ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param StyleManager   $styleManager
     * @param User           $user
     * @param TokenInterface $token
     */
    private function canCreate(StyleManager $styleManager, $user, $token)
    {
        return VoterInterface::ACCESS_GRANTED;
    }

}