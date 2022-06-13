<?php

namespace vezdehod\toaster\pack;

use Exception;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\resourcepacks\ZippedResourcePack;
use vezdehod\toaster\pack\resource\LocalResource;
use vezdehod\toaster\pack\sound\ToastSound;
use vezdehod\toaster\ToastOptions;
use ZipArchive;
use function array_merge;
use function json_encode;
use function md5_file;
use function pathinfo;
use function unlink;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

class RuntimePackBuilder {

    /*private*/
    const UUID_PACK_NAMESPACE = '010bcfd7-ab47-47c2-bc5b-82bbbd809906';
    /*private*/
    const UUID_RESOURCE_NAMESPACE = 'ae26160e-ced6-4467-9a91-a52e6172dc0b';

    //TODO: Split this to something like jsonui-builder
    /*private*/
    const TOAST_COMPONENT_NAME = 'vezdehodui_toast_component';
    /*private*/
    const ICON_COMPONENT_NAME = 'vezdehodui_toast_icon';
    /*private*/
    const FADE_IN_ANIMATION_NAME = 'vezdehodui_toast_anim_fade_in';
    /*private*/
    const STAY_ANIMATION_NAME = 'vezdehodui_toast_anim_stay';
    /*private*/
    const FADE_OUT_ANIMATION_NAME = 'vezdehodui_toast_anim_fade_out';

    /*private*/
    const ICON_PATH = "textures/vezdehodui/toast/icons/";
    /*private*/
    const SOUND_PATH = "sounds/vezdehodui/toast/";
    /*private*/
    const BACKGROUND_SLICE = "textures/vezdehodui/toast/background-slice";

    private /*ZipArchive*/
        $archive;
    private /*string*/
        $checksumSource = '';

    /** @var ToastOptions[] */
    private /*array*/
        $toasts = [];
    private $path;
    private $backgroundSlice;

    public function __construct(
        /*private*/ string $path,
                    string $background,
                    string $backgroundSlice
    ) {
        $this->path = $path;
        @unlink($this->path);
        $this->archive = new ZipArchive();
        $this->archive->open($this->path, ZipArchive::CREATE);

        $this->addFile(self::BACKGROUND_SLICE . "." . pathinfo($background, PATHINFO_EXTENSION), $background);
//        $this->addFile("textures/vezdehodui/toast/background-slice.json", $backgroundSlice);
        $this->backgroundSlice = json_decode(file_get_contents($backgroundSlice), true);
    }


    /**
     * @throws Exception
     */
    public function addToast(ToastOptions $toast)/*: void*/ {
        $this->toasts[] = $toast;

        /** @var IResource $resource */
        foreach ([
                     self::ICON_PATH => $toast->getIcon(),
                 ] as $path => $resource) {
            $resource->fetch();

            $localFile = $resource->getLocalFile();
            if ($localFile === null) {
                continue;
            }
            $target = $toast->getPlugin()->getName() . "_" . $toast->getName() . "." . pathinfo($localFile, PATHINFO_EXTENSION);
            $this->addFile($path . $target, $localFile);
        }
    }

    public function generate(): ResourcePack {
        $this->injectJSONUI();
        $this->injectManifest();
        $this->archive->close();
        return new ZippedResourcePack($this->path);
    }

    private function addFile(string $archive, string $local): void {
        $this->archive->addFile($local, $archive);
        $this->checksumSource .= md5_file($local);
    }

    private function addFileContent(string $archive, string $content)/*: void*/ {
        $this->archive->addFromString($archive, $content);
        $this->checksumSource .= $content;
    }

    private function injectManifest()/*: void*/ {
        $this->addFileContent("manifest.json", json_encode([
            'format_version' => 1,
            'header' => [
                'name' => "VToaster",
                'uuid' => \pocketmine\utils\UUID::fromData(self::UUID_PACK_NAMESPACE, $this->checksumSource)->toString(),
                'description' => "VToaster auto-generated resources",
                'version' => [1, 0, 0],
                'min_engine_version' => [1, 1, 0],
                'author' => 'vk.com/m.vezdehod',
            ],
            'modules' => [
                [
                    'type' => 'resources',
                    'uuid' => \pocketmine\utils\UUID::fromData(self::UUID_RESOURCE_NAMESPACE, $this->checksumSource)->toString(),
                    'version' => [1, 0, 0],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    //TODO: animations, background, position

    private function injectJSONUI()/*: void */ {
        $this->addFileContent("ui/hud_screen.json", json_encode(array_merge(
            [
                'namespace' => 'hud',
            ],
            $this->createRootPanelModification(),
            $this->createToast(),
            $this->createFadeInAnimation(),
            $this->createStayAnimation(),
            $this->createFadeOutAnimation()
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function createRootPanelModification(): array {
        return [
            'root_panel' => [
                'modifications' => [
                    [
                        'array_name' => 'controls',
                        'operation' => 'replace',
                        'control_name' => 'hud_actionbar_text_area',
                        'value' => [
                            [
                                'toast_factory' => [
                                    'type' => 'factory',
                                    'factory' => [
                                        'name' => 'hud_actionbar_text_factory',
                                        'control_ids' => [
                                            "hud_actionbar_text" => "impl@hud." . self::TOAST_COMPONENT_NAME,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createToast(): array {//TODO: This should returns conditional-rendering style selector
        $details = [];
        $details['type'] = 'image';
        $details['texture'] = self::BACKGROUND_SLICE;
        $details['uv'] = [0, 0];
        $details['nineslice_size'] = $this->backgroundSlice['nineslice_size'];
        $details['uv_size'] = $this->backgroundSlice['base_size'];
        $details['inherit_max_sibling_width'] = true;
        $details['min_size'] = [128, 32];
        $details['anchor_from'] = "top_right";
        $details['anchor_to'] = "top_right";
        $details['size'] = ["100%c + 10px", 32];
        $details['offset'] = '@hud.' . self::FADE_IN_ANIMATION_NAME;
        $details['layer'] = 50;

        $details['controls'] = [];

        foreach ($this->toasts as $toast) {
            $texture = $toast->getIcon()->getInPackUsageName();
            $localFile = $toast->getIcon()->getLocalFile();
            if ($localFile !== null) {
                $texture = self::ICON_PATH . $toast->getPlugin()->getName() . "_" . $toast->getName() . "." . pathinfo($localFile, PATHINFO_EXTENSION);
            }
            $details['controls'][] = $toast->getName() . '@' . self::ICON_COMPONENT_NAME => [
                '$icon' => $texture,
                'ignored' => "(\$actionbar_text = (\$actionbar_text - '{$toast->getFlag()}'))",
                'offset' => [3, 0],
            ];
        }

        $details['controls'][] = [
            'toast_label' => [
                'type' => 'label',
                'offset' => [30, 0],
                'anchor_from' => 'left_middle',
                'anchor_to' => 'left_middle',
                'layer' => 51,
                'text' => '$actionbar_text',
            ],
        ];

        return [
            self::ICON_COMPONENT_NAME => [
                'type' => 'image',
                'texture' => '$icon',
                'size' => [24, 24],
                'anchor_from' => 'left_middle',
                'anchor_to' => 'left_middle',
                'layer' => 51,
            ],
            self::TOAST_COMPONENT_NAME => $details,
        ];
    }

    //TODO: Animations, background, position

    private function createFadeInAnimation(): array {
        $details = [];
        $details['anim_type'] = 'offset';
        $details['duration'] = 1;
        $details['easing'] = 'spring';

        $details['from'] = '$from';
        $details['to'] = '$to';

        $details['$from|default'] = ["100%", 0];
        $details['$to|default'] = [0, 0];
        $details['variables'] = [[
            'requires' => '(not $desktop_screen)',
            '$from' => ["100%", 10],
            '$to' => [0, 10],
        ]];

        $details['next'] = '@hud.' . self::STAY_ANIMATION_NAME;

        return [
            self::FADE_IN_ANIMATION_NAME => $details,
        ];
    }

    private function createStayAnimation(): array {

        return [
            self::STAY_ANIMATION_NAME => [
                'duration' => 4,
                'anim_type' => 'wait',
                'next' => '@hud.' . self::FADE_OUT_ANIMATION_NAME,
            ],
        ];
    }

    private function createFadeOutAnimation(): array {
        $details = [];
        $details['anim_type'] = 'offset';
        $details['duration'] = 1;
        $details['easing'] = 'in_sine';

        $details['from'] = '$from';
        $details['to'] = '$to';

        $details['$from|default'] = [0, 0];
        $details['$to|default'] = ["100%c + 12px", 0];
        $details['variables'] = [[
            'requires' => '(not $desktop_screen)',
            '$from' => [0, 10],
            '$to' => ["100%c + 12px", 10],
        ]];

        $details['destroy_at_end'] = 'hud_actionbar_text';

        return [
            self::FADE_OUT_ANIMATION_NAME => $details,
        ];
    }
}