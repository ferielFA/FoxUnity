<?php
// Controller for categories management (admin) – class based, MVC friendly
require_once __DIR__ . '/../model/Categorie.php';
require_once __DIR__ . '/../model/Article.php';

class CategoryController
{
    private array $messages = [];

    /** @var array */
    private array $errors = [];

    private array $categories = [];
    private array $articleCountsByCategory = [];

    public function __construct()
    {
        // Load initial data used by views (arrays, for compatibility)
        $this->categories              = Categorie::getAll();
        $this->articleCountsByCategory = Article::countByCategory();
    }

    /**
     * Entry point called by the view to process the current HTTP request.
     * It only inspects $_POST and does not render anything.
     */
    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $act = $_POST['action'] ?? '';

        switch ($act) {
            case 'add':
                $this->handleAdd();
                break;

            case 'edit':
                $this->handleEdit();
                break;

            case 'delete':
                $this->handleDelete();
                break;

            case 'toggle':
                $this->handleToggle();
                break;

            case 'reorder':
                $this->handleReorder();
                break;
        }

        // After mutations, refresh cached category data
        $this->categories              = Categorie::getAll();
        $this->articleCountsByCategory = Article::countByCategory();
    }

    private function handleAdd(): void
    {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');

        if ($name === '') {
            $this->errors[] = 'Category name is required.';
        }

        if (empty($this->errors)) {
            [$ok, $result] = Categorie::add([
                'nom'         => $name,
                'slug'        => $slug,
                'description' => $_POST['description'] ?? '',
                'active'      => isset($_POST['active']) ? 1 : 0,
            ]);

            if ($ok) {
                $this->messages[] = 'Category added.';
            } else {
                $this->errors[] = $result;
            }
        }
    }

    private function handleEdit(): void
    {
        if (empty($_POST['id'])) {
            $this->errors[] = 'Category ID is required.';
            return;
        }

        $id   = (int) $_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');

        if ($name === '') {
            $this->errors[] = 'Category name is required.';
        }

        if (empty($this->errors)) {
            [$ok, $msg] = Categorie::update($id, [
                'nom'        => $name,
                'slug'       => $slug,
                'description'=> $_POST['description'] ?? '',
                'position'   => $_POST['position'] ?? 0,
                'active'     => isset($_POST['active']) ? 1 : 0,
            ]);

            if ($ok) {
                $this->messages[] = 'Category updated.';
            } else {
                $this->errors[] = $msg;
            }
        }
    }

    private function handleDelete(): void
    {
        if (empty($_POST['id'])) {
            $this->errors[] = 'Category ID is required.';
            return;
        }

        $id          = (int) $_POST['id'];
        [$ok, $msg]  = Categorie::delete($id);

        if ($ok) {
            $this->messages[] = 'Category deleted.';
        } else {
            $this->errors[] = $msg;
        }
    }

    private function handleToggle(): void
    {
        if (empty($_POST['id'])) {
            $this->errors[] = 'Category ID is required.';
            return;
        }

        $id  = (int) $_POST['id'];
        // Use explicit posted value (0 or 1)
        $new = isset($_POST['active']) ? (int) $_POST['active'] : 0;

        if (Categorie::setStatus($id, $new)) {
            $this->messages[] = 'Category status updated.';
        } else {
            $this->errors[] = 'Failed to update status.';
        }
    }

    private function handleReorder(): void
    {
        if (empty($_POST['id'])) {
            $this->errors[] = 'Category ID is required.';
            return;
        }

        $id  = (int) $_POST['id'];
        $pos = (int) ($_POST['position'] ?? 0);

        if (Categorie::reorder($id, $pos)) {
            $this->messages[] = 'Category order updated.';
        } else {
            $this->errors[] = 'Failed to reorder.';
        }
    }

    // -------- Public getters for the view layer --------

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getArticleCountsByCategory(): array
    {
        return $this->articleCountsByCategory;
    }
}

// Simple bootstrap so existing views can keep `require`-ing this file
// and receive the expected variables ($messages, $errors, $categories, $counts)
$__categoryController = new CategoryController();
$__categoryController->handleRequest();

$messages   = $__categoryController->getMessages();
$errors     = $__categoryController->getErrors();
$categories = $__categoryController->getCategories();
$counts     = $__categoryController->getArticleCountsByCategory();

unset($__categoryController);

?>



