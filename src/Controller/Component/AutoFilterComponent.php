<?php
namespace AutoFilter\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Table;
use Cake\Controller\Component\PaginatorComponent;
/**
 * Componente auxiliar de busca.
 * Tem como principal objetivo auxiliar a montagem de conditions com base em termos buscados e model a ser lido
 *
 */
class AutoFilterComponent extends Component {

/**
 * @var array Opções padrão
 */
	private $_options = array();
	private $_controller;

	public function initialize(array $config) {
		$_controller = $this->_registry->getController();
		$this->_options = array_merge($this->_options, $config);
	}

/**
 * Faz uma busca em todos os campos disponíveis com base no termo a ser buscado
 *
 * @param string $termo termo a ser buscado
 * @param Table &$table Table que irá pesquisar
 * @param array $settings Configurações adicionais (conditions adicionais, joins, etc)
 * @throws MissingTableClassException
 * @return array conditions relacionados a busca
 */
	public function buscaGeral($termo, Table &$table = null, $settings = array()) {
		if ($table == null) {
			$model = $this->__Controller->{$this->__Controller->modelClass};
		}
		if (!is_object($model)) {
			throw new MissingModelException($model);
		}

		//array que acumula os fields de busca
		$fields = array();

		//adiciona primeiro os campos do próprio model
		$fields[$model->name] = $model->schema();

		//percorrer os joins quando houver
		if (isset($settings['joins'])) {
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
		}

		//percorre os belongsTo atras de mais campos
		//em caso de recursive -1 o cake não faz join automático
		if ($model->recursive > -1) {
			foreach ($model->belongsTo as $associationName => $association) {
				$modelAssoc = $model->$associationName;

				//somente se não tiver sido incluido esse model
				//somente se o dbConfig for o mesmo (quando o dbconfig não é o mesmo, o cake não faz join automático)
				if (!isset($fields[$modelAssoc->name]) && $modelAssoc->useDbConfig == $model->useDbConfig) {
					$fields[$modelAssoc->name] = $modelAssoc->schema();
				}

			}
		}
		$conditions = $this->__montarConditions($fields, $termo);

		return $conditions;
	}

/**
 * Faz a busca do termo e modifica os conditions do paginator
 *
 * @param string $termo termo a ser pesquisado
 * @param Table &$table Table que irá pesquisar
 * @param PaginatorComponent &$paginator Componente de paginação
 * @return void
 */
	public function buscaGeralPaginada($termo, Table &$table = null, PaginatorComponent &$paginator = null) {
		if ($paginator == null) {
			$paginator = $this->_controller->Paginator;
		}

		/*$conditions = $this->buscaGeral($termo, $table, $paginator->settings);

		if (isset($paginator->settings['conditions']) && is_array($paginator->settings['conditions'])) {
			$paginator->settings['conditions'] += $conditions;
		} else {
			$paginator->settings['conditions'] = $conditions;
		}*/
	}

/**
 * Monta um array de conditions baseado nos fields fornecidos e no termo buscado
 *
 * @param array $fields array com todos os campos a ser analisados
 * @param string $termo termo buscado
 *
 * @return array conditions relativos aos fields e termos passados
 *//*
	private function __montarConditions($fields, $termo) {
		$conditions = array();

		foreach ($fields as $model => $modelFields) {
			foreach ($modelFields as $fieldName => $properties) {
				//analisando o contexto do termo buscado
				//em caso de data formato br
				if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{2,4}$/', $termo)) {
					if ($properties['type'] == 'date' || $properties['type'] == 'datetime') {
						list($d, $m, $y) = explode('/', $termo);
						$y = str_pad($y, 4, '19', STR_PAD_LEFT);
						$conditions["{$model}.{$fieldName}"] = "{$y}-{$m}-{$d}";
					}
				} elseif (preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $termo)) { //em caso de data com -
					if ($properties['type'] == 'date' || $properties['type'] == 'datetime') {
						$conditions["{$model}.{$fieldName}"] = "{$termo}";
					}
				} elseif (preg_match('/^[\-\+]?[0-9]*([\.,][0-9]+)?$/', $termo)) { //em caso de número
					$termo = str_replace(',', '.', $termo);
					if ($properties['type'] == 'integer') {
						$termoBusca = (string)round($termo);
						$conditions["{$model}.{$fieldName}"] = "{$termoBusca}";
					} elseif ($properties['type'] == 'decimal' || $properties['type'] == 'float') {
						$conditions["{$model}.{$fieldName}"] = "{$termo}";
					}
				} else { //em caso de qualquer termo
					if ($properties['type'] == 'string') {
						$conditions[] = "{$model}.{$fieldName} LIKE '%{$termo}%'";
					}
				}
			}
		}

		return array('or' => $conditions);
	}*/
}
