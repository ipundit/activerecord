<?php

use ActiveRecord\Exception\ExpressionsException;
use ActiveRecord\WhereClause;

class WhereClauseTest extends DatabaseTestCase
{
    public function testValues()
    {
        $c = new WhereClause('a=? and b=?', [1, 2]);
        $this->assertEquals([1, 2], $c->values());
    }

    public function testOneVariable()
    {
        $c = new WhereClause('name=?', ['Tito']);
        $this->assertEquals('name=?', $c->to_s($this->connection));
        $this->assertEquals(['Tito'], $c->values());
    }

    public function testArrayVariable()
    {
        $c = new WhereClause('name IN(?) and id=?', [['Tito', 'George'], 1]);
        $this->assertEquals([['Tito', 'George'], 1], $c->values());
    }

    public function testMultipleVariables()
    {
        $c = new WhereClause('name=? and book=?', ['Tito', 'Sharks']);
        $this->assertEquals('name=? and book=?', $c->to_s($this->connection));
        $this->assertEquals(['Tito', 'Sharks'], $c->values());
    }

    public function testToString()
    {
        $c = new WhereClause('name=? and book=?', ['Tito', 'Sharks']);
        $this->assertEquals('name=? and book=?', $c->to_s($this->connection));
    }

    public function testToStringWithArrayVariable()
    {
        $c = new WhereClause('name IN(?) and id=?', [['Tito', 'George'], 1]);
        $this->assertEquals('name IN(?,?) and id=?', $c->to_s($this->connection));
    }

    public function testToStringWithEmptyOptions()
    {
        $c = new WhereClause('name=? and book=?', ['Tito', 'Sharks']);
        $x = [];
        $this->assertEquals('name=? and book=?', $c->to_s($this->connection, false, $x));
    }

    public function testInsufficientVariables()
    {
        $this->expectException(ExpressionsException::class);
        $c = new WhereClause('name=? and id=?', ['Tito']);
        $c->to_s($this->connection);
    }

    public function testNoValues()
    {
        $c = new WhereClause("name='Tito'");
        $this->assertEquals("name='Tito'", $c->to_s($this->connection));
        $this->assertEquals(0, count($c->values()));
    }

    public function testEmptyVariable()
    {
        $a = new WhereClause('name=?', [null]);
        $this->assertEquals('name=?', $a->to_s($this->connection));
        $this->assertEquals([null], $a->values());
    }

    public function testZeroVariable(): void
    {
        $a = new WhereClause('name=?', [0]);
        $this->assertEquals('name=?', $a->to_s($this->connection));
        $this->assertEquals([0], $a->values());
    }

    public function testEmptyArrayVariable(): void
    {
        $a = new WhereClause('id IN(?)', [[]]);
        $this->assertEquals('id IN(?)', $a->to_s($this->connection));
        $this->assertEquals([[]], $a->values());
    }

    public function testEmptyArrayVariableSubstitute(): void
    {
        $a = new WhereClause('id IN(?)', [[]]);
        $this->assertEquals('id IN(NULL)', $a->to_s($this->connection, substitute: true));
        $this->assertEquals([[]], $a->values());
    }

    public function testIgnoreInvalidParameterMarker(): void
    {
        $a = new WhereClause("question='Do you love backslashes?' and id in(?)", [[1, 2]]);
        $this->assertEquals("question='Do you love backslashes?' and id in(?,?)", $a->to_s($this->connection));
    }

    public function testIgnoreParameterMarkerWithEscapedQuote(): void
    {
        $a = new WhereClause("question='Do you love''s backslashes?' and id in(?)", [[1, 2]]);
        $this->assertEquals("question='Do you love''s backslashes?' and id in(?,?)", $a->to_s($this->connection));
    }

    public function testIgnoreParameterMarkerWithBackspaceEscapedQuote(): void
    {
        $a = new WhereClause("question='Do you love\\'s backslashes?' and id in(?)", [[1, 2]]);
        $this->assertEquals("question='Do you love\\'s backslashes?' and id in(?,?)", $a->to_s($this->connection));
    }

    public function testSubstituteOnString(): void
    {
        $a = new WhereClause('name=? and id=?', ['Tito', 1]);
        $this->assertEquals("name='Tito' and id=1", $a->to_s($this->connection, substitute: true));
    }

    public function testSubstituteOnHash(): void
    {
        $a = new WhereClause(['name' => 'Tito', 'id'=> 1]);
        $this->assertEquals("name = 'Tito' AND id = 1", $a->to_s($this->connection, substitute: true));
    }

    public function testSubstituteQuotesScalarsButNotOthers(): void
    {
        $a = new WhereClause('id in(?)', [[1, '2', 3.5]]);
        $this->assertEquals("id in(1,'2',3.5)", $a->to_s($this->connection, substitute: true));
    }

    public function testSubstituteWhereValueHasQuestionMark(): void
    {
        $a = new WhereClause('name=? and id=?', ['??????', 1]);
        $this->assertEquals("name='??????' and id=1", $a->to_s($this->connection, substitute: true));
    }

    public function testSubstituteArrayValue(): void
    {
        $a = new WhereClause('id in(?)', [[1, 2]]);
        $this->assertEquals('id in(1,2)', $a->to_s($this->connection, substitute: true));
    }

    public function testSubstituteEscapeQuotesWithConnectionsEscapeMethod(): void
    {
        $a = new WhereClause('name=?', ["Tito's Guild"]);
        $escaped = $this->connection->escape("Tito's Guild");
        $this->assertEquals("name=$escaped", $a->to_s($this->connection, substitute: true));
    }

    public function testBind(): void
    {
        $a = new WhereClause('name=? and id=?', ['Tito']);
        $a->bind(2, 1);
        $this->assertEquals(['Tito', 1], $a->values());
    }

    public function testBindOverwriteExisting(): void
    {
        $a = new WhereClause('name=? and id=?', ['Tito', 1]);
        $a->bind(2, 99);
        $this->assertEquals(['Tito', 99], $a->values());
    }

    public function testBindInvalidParameterNumber(): void
    {
        $this->expectException(ExpressionsException::class);
        $a = new WhereClause('name=?');
        $a->bind(0, 99);
    }

    public function testSubstituteUsingAlternateValues(): void
    {
        $a = new WhereClause('name=?', ['Tito']);
        $this->assertEquals("name='Tito'", $a->to_s($this->connection, substitute: true));
        $this->assertEquals("name='Hocus'", $a->to_s($this->connection, substitute: true, values: ['Hocus']));
    }

    public function testNullValue(): void
    {
        $a = new WhereClause('name=?', [null]);
        $this->assertEquals('name=NULL', $a->to_s($this->connection, substitute: true));
    }

    public function testHashWithDefaultGlue(): void
    {
        $a = new WhereClause(['id' => 1, 'name' => 'Tito']);
        $this->assertEquals('id = ? AND name = ?', $a->to_s($this->connection));
    }

    public function testHashWithGlue(): void
    {
        $a = new WhereClause([
            'id' => 1,
            'name' => 'Tito'
        ]);
        $this->assertEquals('id = ?, name = ?', $a->to_s($this->connection, glue: ', '));
    }

    public function testHashWithArray(): void
    {
        $a = new WhereClause([
            'id' => 1,
            'name' => ['Tito', 'Mexican']
        ]);
        $this->assertEquals('id = ? AND name IN(?,?)', $a->to_s($this->connection));
    }
}
