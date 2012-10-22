<?php
namespace ElasticaManager\Tests;

use Elastica_Client;
use Elastica_Type;
use Elastica_Index;
use ElasticaManager\Tests\Configuration\TestIndexDataProvider;
use ElasticaManager\Tests\Configuration\TestIndexConfiguration;
use FF\ElasticaManager\ElasticaManager;
use FF\ElasticaManager\IndexManager;

class IndexManagerTest extends ElasticaManagerTestBase
{
	public function testConstruct()
	{
		$configuration = new TestIndexConfiguration(new TestIndexDataProvider());
		$indexName     = $configuration->getName();
		$indexManager  = new IndexManager($this->client, $configuration, $indexName);
		$this->assertEquals($this->indexManager, $indexManager);
		$this->assertEquals($this->client, $indexManager->getClient());
		$this->assertEquals($configuration, $indexManager->getConfiguration());
		$this->assertEquals($indexName, $indexManager->getIndexName());

		// Test different name than default one
		$indexName    = $configuration->getName().'_special';
		$indexManager = new IndexManager($this->client, $configuration, $indexName);
		$this->assertEquals($indexName, $indexManager->getIndexName());
	}

	public function testCreateIndex()
	{
		$indexName = TestIndexConfiguration::NAME;
		$index     = $this->indexManager->create(true);
		$newIndex  = new Elastica_Index($this->client, $indexName);
		$this->assertEquals($newIndex, $index);

		$this->indexManager->delete();
	}

	public function testCreateIndexIfExists()
	{
		$this->setExpectedException('FF\ElasticaManager\Exception\ElasticaManagerIndexExistsException');
		$this->indexManager->create();
		$this->indexManager->create();
	}

	/**
	 * @depends testCreateIndexIfExists
	 */
	public function testIndexExists()
	{
		$this->assertTrue($this->indexManager->indexExists());
	}

	/**
	 * @depends testIndexExists
	 */
	public function testDeleteIndex()
	{
		$response     = $this->indexManager->delete();
		$responseData = $response->getData();
		$this->assertTrue($responseData['ok']);
	}

	public function testCreateIndexDifferentName()
	{
		$indexName    = TestIndexConfiguration::NAME.'_diff';
		$indexManager = $this->_getIndexManager($indexName);
		$indexManager->create(true);

		$this->assertEquals($indexName, $indexManager->getIndexName());
		$this->assertTrue($indexManager->indexExists($indexName));

		$indexManager->delete();
		$this->assertFalse($indexManager->indexExists());
	}

	public function testSetMapping()
	{
		$indexName    = TestIndexConfiguration::NAME.'_mapping_test';
		$indexManager = $this->_getIndexManager($indexName);
		$index        = $indexManager->create(true);
		$mapping      = $index->getMapping();

		$configuration = $indexManager->getConfiguration();
		$types         = $configuration->getTypes();
		foreach ($types as $typeName) {
			$properties     = $mapping[$indexName][$typeName]['properties'];
			$confProperties = $configuration->getMappingProperties(new Elastica_Type($index, $typeName));
			ksort($properties);
			ksort($confProperties);
			$this->assertEquals(array_keys($properties), array_keys($confProperties));
		}

		$indexManager->delete();
	}

	public function testPopulateAll()
	{
		$indexName = TestIndexConfiguration::NAME.'_populate_test';
		$indexManager = $this->_getIndexManager($indexName);

		$test    = $this;
		$closure = function ($i, $total) use ($test) {
			$test->assertGreaterThanOrEqual(4, $total);
		};
		$index   = $indexManager->populate(null, $closure, true);
		$count   = $this->_getTotalDocs($index);
		$this->assertEquals(4, $count);
	}
}