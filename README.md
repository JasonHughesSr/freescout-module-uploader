# freescout-module-uploader
A module you can load manually in the file system, that will then let you upload other modules from inside FreeScout.

This folder holds standalone [FreeScout](https://github.com/freescout-help-desk/freescout)
modules. Each subfolder here is deployed by copying it into the `Modules/` directory of a
FreeScout installation.  Then after the module is activated you can upload other custom modules from inside FreeScout.

- [`ModuleUploader`](./ModuleUploader) — adds an "Upload Module" page to
  FreeScout's admin UI so custom modules can be installed by uploading a
  `.zip` instead of copying files onto the server manually.
