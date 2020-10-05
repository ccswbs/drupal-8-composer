<?php

namespace Drupal\asset_bank\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AssetBankController extends ControllerBase {

    public function callbackHandler(Request $request) {
        $imageUrl = htmlspecialchars(strip_tags($request->query->get('imageUrl')));
        $fileMime = \Drupal::service('file.mime_type.guesser')->guess($imageUrl);
        $addToMediaLibrary = \Drupal::config('asset_bank.settings')->get('asset_bank.add_to_media_library');

        if ($addToMediaLibrary && file_valid_uri(file_build_uri($imageUrl))) {
            $file = File::Create(['uri' => file_build_uri($imageUrl),]);
            $file->save();

            if (strpos($fileMime, 'video')) {
                $bundleType = 'video';
                $fieldType = 'field_media_video_file';
            } else {
                $bundleType = 'image';
                $fieldType = 'field_media_image';
            }

            $drupalMedia = Media::create([
                'name' => $file->getFilename(),
                'bundle' => $bundleType,
                'uid' => \Drupal::currentUser()->id(),
                $fieldType => [
                    'target_id' => $file->id(),
                    'alt' => $file->getFilename(),
                ],
            ]);
            $drupalMedia->save();

            $imageUri = file_url_transform_relative($file->url());
            $mediaUuid = $drupalMedia->uuid();
        } else {
            $imageUri = $imageUrl;
            $mediaUuid = '';
        }

        return array(
            '#type' => 'markup',
            '#markup' => $this->t("Returning to editor..."),
            '#attached' => array(
                'library' => array(
                    'asset_bank/assetBankCallback'
                ),
                'drupalSettings' => array(
                    'asset_bank' => array(
                        'assetBankCallback' => array(
                            'imageUri' => $imageUri,
                            'mimeType' => $fileMime,
                            'mediaUuid' => $mediaUuid,
                        ),
                    ),
                ),
            ),
        );
    }
}