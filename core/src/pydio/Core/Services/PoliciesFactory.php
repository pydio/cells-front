<?php
/*
 * Copyright 2007-2017 Abstrium <contact (at) pydio.com>
 * This file is part of Pydio.
 *
 * Pydio is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Pydio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Pydio.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <https://pydio.com/>.
 */
namespace Pydio\Core\Services;

use Pydio\Core\Model\UserInterface;
use Swagger\Client\Model\ServiceResourcePolicy;
use Swagger\Client\Model\ServiceResourcePolicyAction;
use Swagger\Client\Model\ServiceResourcePolicyPolicyEffect;

defined('PYDIO_EXEC') or die('Access not allowed');

class PoliciesFactory {

    /**
     * @param $owner UserInterface
     * @param $policies ServiceResourcePolicy[]
     * @return bool
     */
    public static function isOwner($policies, $owner) {
        foreach($policies as $policy) {
            if ($policy->getAction() === ServiceResourcePolicyAction::OWNER) {
                return $policy->getSubject() === $owner->getUuid();
            }
        }
        return false;
    }

    /**
     * @param $policies ServiceResourcePolicy[]
     * @param $newOwner UserInterface
     * @return ServiceResourcePolicy[]
     */
    public static function replaceOwner($policies, $newOwner) {
        $newPolicies = [];
        foreach($policies as $policy) {
            if ($policy->getAction() === ServiceResourcePolicyAction::OWNER) {
                $newPolicies[] = (new ServiceResourcePolicy())->setSubject($newOwner->getUuid())->setAction(ServiceResourcePolicyAction::OWNER)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW);
            } else {
                $newPolicies[] = $policy;
            }
        }
        return $newPolicies;
    }

    /**
     * @param $policies ServiceResourcePolicy[]
     * @param $subject UserInterface
     * @return bool
     */
    public static function subjectCanWrite($policies, $subject) {
        return self::subjectCanAction($policies, $subject, ServiceResourcePolicyAction::WRITE);
    }

    /**
     * @param $policies ServiceResourcePolicy[]
     * @param $subject UserInterface
     * @param $action string
     */
    public static function subjectCanAction($policies, $subject, $action) {
        $subjects = [
            "user:" . $subject->getId(),
            "profile:" . $subject->getProfile(),
        ];
        foreach($subject->getRolesKeys() as $roleKey){
            $subjects[] = "role:" . $roleKey;
        }
        $hasAllow = false;
        foreach($policies as $policy) {
            if ($policy->getAction() !== $action) continue;
            foreach($subjects as $sub){
                if ($policy->getSubject() === $sub) {
                    if ($policy->getEffect() === ServiceResourcePolicyPolicyEffect::DENY){
                        return false; // A DENY BREAKS DIRECTLY
                    }
                    $hasAllow = true;
                }
            }
        }
        return $hasAllow;
    }

    /**
     * @param $owner UserInterface
     * @return array
     */
    public static function defaultAdminResource($owner){
        return [
            (new ServiceResourcePolicy())->setSubject($owner->getUuid())->setAction(ServiceResourcePolicyAction::OWNER)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("profile:standard")->setAction(ServiceResourcePolicyAction::READ)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("profile:admin")->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
        ];
    }

    /**
     * Create user only read/write policies
     * @param $owner UserInterface
     * @return array
     */
    public static function policiesForUniqueUser($owner) {
        $userUuid = $owner->getUuid();
        $userLogin = $owner->getId();
        return[
            (new ServiceResourcePolicy())->setSubject($userUuid)->setAction(ServiceResourcePolicyAction::OWNER)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("user:$userLogin")->setAction(ServiceResourcePolicyAction::READ)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("user:$userLogin")->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("profile:admin")->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
        ];
    }

    /**
     * Create policies for a shared user
     * Let user edit himself, Let admin edit this user as well
     * @param $owner UserInterface
     * @param $targetUserId string
     * @return ServiceResourcePolicy[]
     */
    public static function policiesForSharedUser($owner, $targetUserId)
    {
        $parentUserId = $owner->getId();
        return [
            (new ServiceResourcePolicy())->setSubject($owner->getUuid())->setAction(ServiceResourcePolicyAction::OWNER)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("user:" . $parentUserId)->setAction(ServiceResourcePolicyAction::READ)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("user:" . $targetUserId)->setAction(ServiceResourcePolicyAction::READ)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("user:" . $parentUserId)->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("user:" . $targetUserId)->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("profile:admin")->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
        ];
    }

    /**
     * Create policies for a standard user.
     * All people can see the user, only admin and the user can edit
     * @param $owner UserInterface
     * @param $userId
     * @return ServiceResourcePolicy[]
     */
    public static function policiesForStandardUserRole($owner, $userId)
    {
        return [
            (new ServiceResourcePolicy())->setSubject($owner->getUuid())->setAction(ServiceResourcePolicyAction::OWNER)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("profile:standard")->setAction(ServiceResourcePolicyAction::READ)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("profile:admin")->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
            (new ServiceResourcePolicy())->setSubject("user:$userId")->setAction(ServiceResourcePolicyAction::WRITE)->setEffect(ServiceResourcePolicyPolicyEffect::ALLOW),
        ];
    }
}