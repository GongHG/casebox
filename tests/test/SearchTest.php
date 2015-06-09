<?php
namespace CB\UNITTESTS;

/**
 * Description of SearchTest
 *
 * @author ghindows
 */
class SearchTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->oldValues = array(
            'user_id' => $_SESSION['user']['id']
            ,'userVerified' => empty($_SESSION['verified'])
            ,'solrIndexing' => \CB\Config::getFlag('disableSolrIndexing')
        );

        $_SESSION['verified'] = true;

        \CB\Config::setFlag('disableSolrIndexing', true);
    }

    public function queriesDataProvider()
    {
        return \CB\UNITTESTS\DATA\searchQueriesData();
    }

    /**
     * @dataProvider queriesDataProvider
     */
    public function testSearchQueries($data)
    {
        $search = new \CB\Api\Search();

        $result = $search->query($data['query']);

        $this->assertArraySubset(
            $data['result'],
            $result
        );

    }

    /**
     * test search exception
     */
    public function testSearchException()
    {
        $search = new \CB\Api\Search();

        try {
            $result = $search->query(
                [
                    'strictSort' => 'erorrneous_data_to_receive_exception asc'
                ]
            );

            $this->assertTrue(false, 'No exception on wrong date');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $result = \CB\Search::getObjects(1, '"erorrneous field list\/');

            $this->assertTrue(false, 'No exception on getObjects');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * test search shortcut
     */
    public function testSearchShortcut()
    {
        $data = '{"action":"CB_Browser_Actions","method":"shortcut","data":[{"action":"shortcut","targetData":{"id":"1","name":"Tree","system":1,"template_id":"5","dstatus":"0","path":"/0/1"},"sourceData":[{"id":"1","name":"All Folders"}]}],"type":"rpc","tid":59}';

        \CB\Config::setFlag('disableSolrIndexing', false);

        $result = \CB\UNITTESTS\HELPERS\getIncludeContents(
            \CB\DOC_ROOT.'remote/router.php',
            [ 'postdata' => $data]
        );

        \CB\Config::setFlag('disableSolrIndexing', true);

        $result = json_decode($result, true);

        $this->assertArraySubset(
            ['result' => ['success' => true]],
            $result
        );

        $search = new \CB\Api\Search();

        $result = $search->query(
            [
                'pids' => 1,
                'template_types' => 'shortcut'
            ]
        );

        $this->assertArraySubset(
            ['success' => true],
            $result
        );
    }

    public function testSearchUser()
    {

        //add a user for search testing
        $class = new \CB\UsersGroups();

        $data = $class->addUser(
            [
                'name' => 'searchtest',
                'first_name' => 'Search',
                'last_name' => 'Test',
                'email' => 'SearchEmail@test.com'
            ]
        );

        $_SESSION['user']['id'] = $data['data']['id'];

        $datas = \CB\UNITTESTS\DATA\searchQueriesData();

        $search = new \CB\Api\Search();

        foreach ($datas as $data) {
            $result = $search->query($data[0]['query']);

            $this->assertArraySubset(
                $data[0]['result'],
                $result
            );
        }

        $_SESSION['user']['id'] = 1;
    }

    public function searchDataProvider()
    {
        return \CB\UNITTESTS\DATA\getBasicSearchData();
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch($search)
    {
        $src = new \CB\Search();

        $this->assertTrue($src->ping() > 0);

        $src_response = $src->search('test', 0, 10, []);

        $this->assertEquals(
            'OK',
            $src_response->getHttpStatusMessage(),
            $src_response->getHttpStatusMessage()
        );

        $result = \CB\UNITTESTS\HELPERS\getIncludeContents(
            \CB\DOC_ROOT.'remote/router.php',
            [ 'postdata' => $search['postdata']]
        );

        $result = json_decode($result, true);

        $this->assertArraySubset(
            json_decode($search['expected_response'], true),
            $result
        );

    }

    protected function tearDown()
    {
        $_SESSION['user']['id'] = $this->oldValues['user_id'];

        \CB\Config::setFlag('disableSolrIndexing', $this->oldValues['solrIndexing']);

        if (empty($this->oldValues['userVerified'])) {
            unset($_SESSION['verified']);
        }
    }
}
