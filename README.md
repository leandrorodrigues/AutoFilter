# AutoFilter plugin for CakePHP


## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org)

## Exemplo de uso
```
class AdministradoresController extends AdminController
{

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {

        $query = $this->AutoFilter->generateQuery();
        $administradores = $this->paginate($query);

        $this->set('administradores', $administradores);
        $this->set('_serialize', ['administradores']);
    }
}
```
