<?php
define("CATEGORY_PATH","../resources/categories/");
define('NEW_CATEGORY_RULES',serialize(
    array(
        'titulo' => 'required|unique:categoria,nombre',
        'imagen' => 'required|image',
        'categoria_padre' => 'required'
    )
)
);
define('EDIT_CATEGORY_RULES',serialize(
    array(
        'titulo' => 'required',
        'imagen' => 'image',
        'categoria_padre' => 'required'
    )
)
);
/*
|------------------------------------------------------------
|              Controlador para las categorias
|------------------------------------------------------------
*/
class CategoryController extends BaseController {

    /*
    |------------------------------------------------------------
    |         Función que permite agregar una categoria
    |------------------------------------------------------------
    */
    public function newCategory() {
        if (ValidationManager::isValid(Input::all(),unserialize(NEW_CATEGORY_RULES))) {
            $category = new Category();
            $category->nombre = Input::get('titulo');
            $category->id_categoria_padre = Input::get('categoria_padre');
            $category->url_imagen = FileManager::moveFile(Input::file('imagen'),CATEGORY_PATH.$category->nombre.'/');
            $category->url_video = FileManager::moveFile(Input::file('video'),CATEGORY_PATH.$category->nombre.'/');
            $category->save();
            return Redirect::to('admin');
        } else {
            print_r(ValidationManager::getFails(Input::all(),unserialize(NEW_CATEGORY_RULES)));
        }
    }

    /*
    |------------------------------------------------------------
    |         Función que permite agregar una categoria
    |------------------------------------------------------------
    */
    public function editCategory($idCategory) {
        if (ValidationManager::isValid(Input::all(),unserialize(EDIT_CATEGORY_RULES))) {
            $category = Category::findOrFail($idCategory);

            $oldFolderName = $category->nombre;

            //Se verifica si existe otro gesto con el título nuevo
            $nameExists = Category::where('nombre', Input::get('titulo'))
                ->where('id_categoria', '<>', $idCategory)
                ->count();

            //Si el título nuevo está disponible se adopta, y se renombra la carpeta del gesto
            if (!$nameExists) {
                $category->nombre = Input::get('titulo');
                FileManager::rename(CATEGORY_PATH.$oldFolderName, CATEGORY_PATH.$category->nombre);
            }

            $category->id_categoria_padre = Input::get('categoria_padre');

            $image = Input::file('imagen');
            if (!empty($image)) {
                File::delete($category->url_imagen);
                $category->url_imagen = FileManager::moveFile(Input::file('imagen'),CATEGORY_PATH.$category->nombre.'/');
            }

            $video = Input::file('video');
            if (!empty($video)) {
                File::delete($category->url_video);
                $category->url_video = FileManager::moveFile(Input::file('video'),CATEGORY_PATH.$category->nombre.'/');
            }

            $category->save();

            return Redirect::to('admin');
        } else {
            print_r(ValidationManager::getFails(Input::all(),unserialize(NEW_CATEGORY_RULES)));
        }
    }

    /*
    |------------------------------------------------------------
    |         Función que permite eliminar una categoria
    |------------------------------------------------------------
    */
    public function deleteCategory($id) {
        $category = Category::with('gestures')->findOrFail($id);

        if (count($category->gestures) == 0) {
            FileManager::deleteDir(CATEGORY_PATH.FileManager::clean($category->nombre));
            $category->delete();
            echo 'Eliminar';
        }
        else {
            $category->status = !$category->status;
            $category->save();
            echo 'Desactivar';
        }
        return Redirect::to('admin');
    }

    public function deleteEntireCategory($id) {
        $category = Category::findOrFail($id);
        FileManager::deleteDir(CATEGORY_PATH.FileManager::clean($category->nombre));
        $category->delete();

        return Redirect::to('admin');
    }

}