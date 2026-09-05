<?php

namespace Modules\ModuleUploader\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModuleUploaderController extends Controller
{
    // Max accepted archive size, in kilobytes (Laravel's "max" validation rule).
    const MAX_ZIP_KB = 51200; // 50 MB

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Upload form.
     */
    public function index(Request $request)
    {
        return view('moduleuploader::index', [
            // Only used by the shared modules/sidebar_menu partial to decide
            // whether to show the "Installed Modules" anchor link.
            'installed_modules' => \Module::all(),
        ]);
    }

    /**
     * Handle the uploaded zip: validate it and drop it into the Modules folder.
     * The module still has to be activated from the core Modules page - we
     * never auto-activate code we just received over HTTP.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'module_zip' => 'required|file|mimes:zip|max:'.self::MAX_ZIP_KB,
        ]);

        try {
            $module_dir_name = $this->validateAndExtract($request->file('module_zip')->getRealPath());
        } catch (\Exception $e) {
            return back()->withErrors(['module_zip' => $e->getMessage()]);
        }

        \Module::clearCache();

        \Session::flash('flash_success_floating', __('Module uploaded successfully').' ('.$module_dir_name.'). '.__('Activate it below to finish installing it.'));

        return redirect()->route('modules');
    }

    /**
     * Validate the uploaded zip and extract it into the Modules folder.
     * Returns the name of the extracted module folder on success.
     *
     * @throws \Exception
     */
    protected function validateAndExtract(string $zip_path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($zip_path) !== true) {
            throw new \Exception(__('Uploaded file is not a valid zip archive'));
        }

        try {
            $top_level_dir = $this->getModuleFolderName($zip);
            $module_json = $this->getModuleJson($zip, $top_level_dir);
            $alias = $this->getValidatedAlias($module_json);

            $modules_path = \Module::getPath();
            $destination = $modules_path.DIRECTORY_SEPARATOR.$top_level_dir;

            if (file_exists($destination)) {
                throw new \Exception(__('A module folder with this name already exists. Delete the existing module first').': '.$top_level_dir);
            }

            // Refuse to shadow an already installed module registered under a
            // different folder name but with the same alias.
            \Module::clearCache();
            if (\Module::findByAlias($alias)) {
                throw new \Exception(__('A module with this alias is already installed').': '.$alias);
            }

            $this->extract($zip, $top_level_dir, $destination);
        } finally {
            $zip->close();
        }

        return $top_level_dir;
    }

    /**
     * Walk every entry of the archive, rejecting anything that could escape
     * the destination folder (zip-slip), and return the single top-level
     * folder name every entry must live under.
     *
     * @throws \Exception
     */
    protected function getModuleFolderName(\ZipArchive $zip): string
    {
        $top_level_dir = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name === false || $name === '') {
                continue;
            }

            $name = str_replace('\\', '/', $name);

            // Absolute paths, drive letters, and ".." segments could all be
            // used to write outside of the Modules folder - reject them.
            if ($name[0] === '/' || preg_match('#^[A-Za-z]:#', $name) || strpos($name, '..') !== false) {
                throw new \Exception(__('Zip archive contains an invalid file path').': '.$name);
            }

            $dir = explode('/', $name)[0];

            if ($dir === '') {
                throw new \Exception(__('Zip archive contains an invalid file path').': '.$name);
            }

            if ($top_level_dir === null) {
                $top_level_dir = $dir;
            } elseif ($dir !== $top_level_dir) {
                throw new \Exception(__('Zip archive must contain a single module folder at its root'));
            }
        }

        if (!$top_level_dir || !preg_match('#^[A-Za-z0-9_\-]+$#', $top_level_dir)) {
            throw new \Exception(__('Zip archive must contain a single module folder at its root'));
        }

        return $top_level_dir;
    }

    /**
     * Read and decode module.json from inside the archive without
     * extracting anything yet.
     *
     * @throws \Exception
     */
    protected function getModuleJson(\ZipArchive $zip, string $top_level_dir): array
    {
        $raw = $zip->getFromName($top_level_dir.'/module.json');

        if ($raw === false) {
            throw new \Exception(__('module.json not found in the uploaded module'));
        }

        $module_json = json_decode($raw, true);

        if (!is_array($module_json)) {
            throw new \Exception(__('module.json is not valid JSON'));
        }

        return $module_json;
    }

    /**
     * @throws \Exception
     */
    protected function getValidatedAlias(array $module_json): string
    {
        $alias = $module_json['alias'] ?? '';
        $sanitized = preg_replace('#[^a-zA-Z0-9_\-]#', '', $alias);

        if ($sanitized === '' || $sanitized !== $alias) {
            throw new \Exception(__('module.json is missing a valid "alias"'));
        }

        return strtolower($sanitized);
    }

    /**
     * Extract to a temp folder first, then atomically move only the
     * validated module folder into place.
     *
     * @throws \Exception
     */
    protected function extract(\ZipArchive $zip, string $top_level_dir, string $destination): void
    {
        $extract_to = sys_get_temp_dir().DIRECTORY_SEPARATOR.'moduleuploader_'.uniqid();

        if (!$zip->extractTo($extract_to)) {
            throw new \Exception(__('Failed to extract the zip archive'));
        }

        $extracted_module_dir = $extract_to.DIRECTORY_SEPARATOR.$top_level_dir;

        try {
            if (!is_dir($extracted_module_dir)) {
                throw new \Exception(__('Failed to extract the zip archive'));
            }

            if (!\File::moveDirectory($extracted_module_dir, $destination)) {
                throw new \Exception(__('Failed to move the module into the Modules folder. Check folder permissions').': '.dirname($destination));
            }
        } finally {
            \File::deleteDirectory($extract_to);
        }
    }
}
