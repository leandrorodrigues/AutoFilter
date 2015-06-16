<?php
namespace AutoFilter\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Table;
use TermEvaluation\Evaluator;
/**
 * Componente auxiliar de busca.
 * Tem como principal objetivo auxiliar a montagem de conditions com base em termos buscados e model a ser lido
 *
 */
class AutoFilterComponent extends Component {

/**
 * @var array Opções padrão
 */
	private $_options = array(
		'queryKey' => 'q'
	);

/**
 * @var Controller Controller que fez a chamada
 */
	private $_controller;

	public function initialize(array $config) {
		$this->_controller = $this->_registry->getController();
		$this->_options = array_merge($this->_options, $config);
	}

/**
 * Faz uma busca em todos os campos disponíveis com base no termo a ser buscado
 *
 * @param string $term termo a ser buscado
 * @param Table &$table Table que irá pesquisar
 * @param array $config Configurações adicionais
 * @throws MissingTableClassException
 * @return array conditions relacionados a busca
 */
	public function generateWhere($term, Table &$table = null, $config = array()) {
		if ($table == null) {
			$table = $this->_getTable();
		}
		if (!is_object($table)) {
			throw new MissingTableClassException($table);
		}

		//array que acumula os fields de busca
		$fields = array();

		//adiciona primeiro os campos do próprio model
		$fields[$table->alias()] = $table->schema()->columns();

		//percorrer os joins quando houver
		/*if (isset($settings['joins'])) {
			foreach ($settings['joins'] as $join) {
				//nome da classe conforme o nome da tabela do join
				$modelName = Inflector::classify($join['table']);

				//tentar ler model, caso exista um model relacionado ao nome do join
				if (App::import('Model', $modelName)) {
					$modelJoin = new $modelName();
					if (!isset($fields[$modelJoin->name])) {
						$fields[$modelJoin->name] = $modelJoin->schema();
					}
				}
			}
		}*/

		//percorre os belongsTo atras de mais campos
		//em caso de recursive -1 o cake não faz join automático
		/*if ($model->recursive > -1) {
			foreach ($model->belongsTo as $associationName => $association) {
				$modelAssoc = $model->$associationName;

				//somente se não tiver sido incluido esse model
				//somente se o dbConfig for o mesmo (quando o dbconfig não é o mesmo, o cake não faz join automático)
				if (!isset($fields[$modelAssoc->name]) && $modelAssoc->useDbConfig == $model->useDbConfig) {
					$fields[$modelAssoc->name] = $modelAssoc->schema();
				}

			}
		}*/

		$where = array();

		//analizando tipo do termo antes de percorrer os campos.
		$evaluator = new Evaluator();
		$evaluatedTerm = $evaluator->evaluate($term);

		foreach ($fields as $tableName => $tableFields) {
			foreach ($tableFields as $fieldName) {
				$fieldType = $table->schema()->columnType($fieldName);
				$whereField = $this->_makeWhere($evaluatedTerm, $tableName . '.' . $fieldName, $fieldType);
				if($whereField) {
					$where = array_merge($whereField, $where);
				}
			}
		}

		return ['OR' => $where];
	}

	/**
	 * @param Table|null $table
	 * @param array $settings
	 *
	 * @return \Cake\ORM\Query Query com as condições para o filtro
	 */
	public function generateQuery(Table &$table = null, $config = array()) {
		if ($table == null) {
			$table = $this->_getTable();
		}
		if (!is_object($table)) {
			throw new MissingTableClassException($table);
		}

		$this->_options += $config;

		$query = $table->find();


		if (!empty($this->request->query[$this->_options['queryKey']])) {
			$query = $query->where($this->generateWhere($this->request->query[$this->_options['queryKey']], $table, $config));
		}

		return $query;
	}

	private function _getTable() {
		$modelClass = $this->_controller->modelClass;
		list(, $tableName) = pluginSplit($modelClass);

		$table = $this->_controller->{$tableName};
		return $table;
	}

	/**
	 * Monta um where a partir do termo e do campo passado de acordo com as combinações de tipo.
	 *
	 * @param $term string termo a ser pesquisado
	 * @param $fieldName string campo a ser pesquisado
	 * @param $fieldType string tipo do campo
	 *
	 * @return array parâmetros where montados
	 */
	private function _makeWhere($term, $fieldName, $fieldType) {
		$termType = is_object($term)? get_class($term): gettype($term);
		$where = [];

		if (($fieldType == 'date' || $fieldType == 'datetime') && $termType == 'DateTime') {
			$where[] = [$fieldName => $term->format('Y-m-d') ];
		}
		elseif ($fieldType == 'integer' && $termType == 'integer') {
			$where[] = [$fieldName => (string)$term];
		}
		elseif (($fieldType == 'decimal' || $fieldType == 'float') && $termType == 'double') {
			$where[] = [$fieldName => (string)$term];
		}
		//em caso de qualquer termo
		else {
			if ($fieldType == 'string' && $termType == 'string') {
				$where[] = "{$fieldName} LIKE '%{$term}%'";
			}
		}

		if(empty($where)) {
			return false;
		}

		return $where;
	}
}
