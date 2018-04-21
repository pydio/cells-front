<?php
/*
 * Copyright 2007-2017 Charles du Jeu - Abstrium SAS <team (at) pyd.io>
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
 * The latest code can be found at <https://pydio.com>.
 */

namespace Pydio\Tests\Atomics;

use Pydio\Conf\Core\Role;

class RolesTest extends \PHPUnit_Framework_TestCase
{

    public function testRolesNumericKeys()
    {
        $r1 = new Role("role1");
        $r2 = new Role("role2");

        $r1->setAcl(1, "read,write");
        $r3 = $r2->override($r1);
        $this->assertEquals("read,write", $r3->getAcl(1));

    }

    public function testRolesAclAdditivity()
    {
        $r1 = new Role("role1");
        $r2 = new Role("role2");

        $r1->setAcl("repository_id", "");
        $r2->setAcl("repository_id", "write");
        $r3 = $r2->override($r1);
        $this->assertEquals("write", $r3->getAcl("repository_id"));

        $r1->setAcl("repository_id", "read");
        $r2->setAcl("repository_id", "write");
        $r3 = $r2->override($r1);
        $this->assertEquals("write", $r3->getAcl("repository_id"));

        $r1->setAcl("repository_id", "read");
        $r2->setAcl("repository_id", "");
        $r3 = $r2->override($r1);
        $this->assertEquals("read", $r3->getAcl("repository_id"));

        $r1->setAcl("repository_id", "read");
        $r2->setAcl("repository_id", PYDIO_VALUE_CLEAR);
        $r3 = $r2->override($r1);
        $this->assertEquals(PYDIO_VALUE_CLEAR, $r3->getAcl("repository_id"));

    }

    public function testRolesParametersAdditivity()
    {
        $r1 = new Role("role1");
        $r2 = new Role("role2");

        $r1->setParameterValue("type.id", "param_name", "param_value1", "repository_id");
        $this->assertEquals("param_value1", $r1->filterParameterValue("type.id", "param_name", "repository_id", "anyvalue1"));

        $r2->setParameterValue("type.id", "param_name", "param_value2", "repository_id");
        $r3 = $r2->override($r1);
        $this->assertEquals("param_value2", $r3->filterParameterValue("type.id", "param_name", "repository_id", "anyvalue"));

        $r1->setParameterValue("type.id", "param_name", "param_value1", "repository_id");
        $r2->setParameterValue("type.id", "param_name", PYDIO_VALUE_CLEAR, "repository_id");
        $r3 = $r2->override($r1);
        $this->assertEquals("anyvalue2", $r3->filterParameterValue("type.id", "param_name", "repository_id", "anyvalue2"));

        $r1->setParameterValue("type.id", "param_name", "param_value1", "repository_id");
        $r2->setParameterValue("type.id", "param_name", "", "repository_id");
        $r3 = $r2->override($r1);
        $this->assertEquals("param_value1", $r3->filterParameterValue("type.id", "param_name", "repository_id", "anyvalue2"));

    }

    public function testRolesActionsAdditivity()
    {
        $r1 = new Role("role1");
        $r2 = new Role("role2");

        $r1->setActionState("type.id", "action_name", "repository_id", "disabled");
        $this->assertFalse($r1->actionEnabled("type.id", "action_name", "repository_id", true));
        $r1->setActionState("type.id", "action_name", "repository_id", "enabled");
        $this->assertTrue($r1->actionEnabled("type.id", "action_name", "repository_id", true));

        $r2->setActionState("type.id", "action_name", "repository_id", "enabled");
        $r3 = $r2->override($r1);
        $this->assertTrue($r3->actionEnabled("type.id", "action_name", "repository_id", true));

    }

}
