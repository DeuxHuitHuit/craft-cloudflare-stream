# Change log

We try to maintain a complete change log, based on what is available in git.

## 2.2.0 - 2024-10-23

Before this version, it was possible to upload non-video files in assets that have
a Cloudflare Stream field. Since this creates an error, we opt into preventing it.
We always recommend to create a dedicated file system and volume for your stream assets.

* ad4edfa373 (feat) Prevent uploads of non-video files
* 1c994b21b2 (fix) Use field from field layout instead
* 9023324bbf (fix) Error in translation

## 2.1.0 - 2024-10-02

* 911f086cdf (feat) Add TUS support (#14)
* d52d1ecdf8 (fix) Use `path` property instead of `rootPath` (#13)

## 2.0.0 - 2024-08-13

This is a breaking change since v2 only support Craft 5.

* a03694fc13 (fix) Check for rootPath property (#8, thanks @curtishenson)
* de79933d08 (bc) Move to Craft 5 (#10)

## 1.5.0 - 2024-06-05

* 4f1592f408 (feat) Add the ability to control the thumbnail

## 1.4.8 - 2024-03-28

* 32271b8440 (fix) Make sure variables are defined

## 1.4.7 - 2024-02-20

* 98b8919381 (fix) Make sure videoName is always the filename

## 1.4.6 - 2024-02-08

* d0ab33081a (chore) Add php 8.3 support

## 1.4.5 - 2023-12-04

* f25ee33c80 (fix) Add warning outside of the details
* baee3048ca (fix) Make sure we compare with a float
* e9ab621cae (fix) Make sure removing from CF works: Fix the regression from 1.4.3.
* 07b515c0a4 (fix) Poll the video until the process is complete: Fixes issues with mp4 urls.

## 1.4.4 - 2023-11-15

* 87ce93d209 Trim trailing / on video path

## 1.4.3 - 2023-11-13

* 6abeaa8c0b (fix) Properly handle errors
* b74863a835 (fix) Make sure the file exists before upload
* 05998c16bb (fix) Properly compute the asset's path

## 1.4.2 - 2023-11-09

* 335db74590 Fix broken video path

## 1.4.1 - 2023-10-30

* daca75ea12 Add errors + update help text
* 9307dbb34c Add schema version prop

## 1.4.0 - 2023-10-16

* ae76646657 (feat) Add support for uploads via form data (#5)
* 5ab0e3174f (fix) Prevent overflow of the progressLabel field (#4)
* 6c27238580 (fix) lower ttr to 2 minutes (#3)

## 1.3.0 - 2023-10-16

* 4163fd0012 (feat) Add reupload cli command (#2)

## 1.2.8 - 2023-10-12

* dcf26c85f9 (fix) Validate the config upon client creation (#1)

## 1.2.7 - 2023-08-30

* 3e8badccee Try a different syntax for php req

## 1.2.6 - 2023-07-26

* 9c71085fa6 Fix a bugs in graphql Object Type
* 902e1922cf Add a graphql example in the docs

## 1.2.5 - 2023-07-26

* 20d211dc32 Updated icon

## 1.2.4 - 2023-07-25

* 2e955cba59 New icon

## 1.2.3 - 2023-07-25

* 4ad1155e26 Fix a bug with settings validation

## 1.2.2 - 2023-07-25

* 7550b37544 Add missing 1.2.1 change log entry

## 1.2.1 - 2023-07-25

* 2d7423212d Fix changelog formatting

## 1.2.0 - 2023-07-25

* a2463c8a48 Save and display polling status
* 66a3fd7441 Add thumbnails and previews in assets panel
* 99c4831e7e Better error handling

## 1.1.0 - 2023-07-25

* 8716878bbc Add support for deleted/restored assets
* 50f2938a91 Add autoUpload
* Bug fixes

## 1.0.2 - 2023-07-24

- Bug fixes
- Added phpcsfixer
- Added last result to retry-able jobs

## 1.0.1 - 2023-07-24

- Bug fixes
- Added a README

## 1.0.0 - 2023-07-24

- Initial version
