<?php
/* @var $model humhub\modules\custom_pages\modules\template\models\RichtextContent */
/* @var $form \humhub\modules\ui\form\widgets\ActiveForm */

use yii\helpers\Url;
use humhub\libs\Html;

$id = 'ckeditor_' . $model->id;
$id .= ($model->id == null) ? preg_replace( "/(\[|\])/","", $model->formName() )   : $model->id;

$csrfTokenName = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;


// Todo: use new ContentContainerHelper class prior to 1.3
$sguid = Yii::$app->request->get('sguid') ? Yii::$app->request->get('sguid') : Yii::$app->request->get('cguid');
$uploadUrl = Url::to(['/custom_pages/template/upload/upload-ckeditor-file', 'sguid' => $sguid]);

?>

<?= $form->field($model, 'content')->textarea(['id' => $id, 'rows' => 6, 'class' => 'ckeditorInput', 'data-form-name' => $model->formName()])->label(false); ?>

<?php foreach ($model->fileList as $file) : ?>
    <?= Html::hiddenInput($model->formName().'[fileList][]', $file); ?>
<?php endforeach; ?>

<?= Html::beginTag('script') ?>
    var ckeditorAddUploadedFile = function (guid) {
        var form = $(CKEDITOR.currentInstance.container.$).closest('form');
        var modelFormName = $(CKEDITOR.currentInstance.element.$).data('form-name');
        $(form).append('<input type="hidden" name="'+modelFormName+'[fileList][]" value="' + guid + '" />');
    };

    (function () {
        var id = '<?= $id ?>';
        var $input = $('#' + id);

        var initBasicEditor = function (id) {
            return initFullEditor(id);
            $('#' + id).data('preset', 'basic');
            return initCkEditor(id, [
                {name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                {name: 'links'},
                {name: 'insert'},
                {name: 'paragraph', groups: ['list']},
                {name: 'styles'},
                {name: 'colors'},
                {name: 'showmore'}
            ], '<?= Yii::t('CustomPagesModule.widgets_views_richtextContentEditForm', 'more'); ?>');
        };

        var initFullEditor = function (id) {
            $('#' + id).data('preset', 'full');
            return initCkEditor(id, [
                { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
                { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
                { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
                { name: 'forms', groups: [ 'forms' ] },
                '/',
                { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
                { name: 'links', groups: [ 'links' ] },
                { name: 'insert', groups: [ 'insert' ] },
                '/',
                { name: 'styles', groups: [ 'styles' ] },
                { name: 'colors', groups: [ 'colors' ] },
                { name: 'tools', groups: [ 'tools' ] },
                { name: 'others', groups: [ 'others' ] },
                {name: 'showmore'}
            ], '<?= Yii::t('CustomPagesModule.widgets_views_richtextContentEditForm', 'less'); ?>');
        };

        var initCkEditor = function (id, toolbars, buttonLabel) {
            var instance = CKEDITOR.replace(id, {
                'inline': false,
                'skin': 'bootstrapck',
                'removeButtons': 'Flash',
                'filebrowserUploadUrl': '<?= $uploadUrl ?>',
                'filebrowserUploadMethod': 'form',
                 toolbarGroups: toolbars
            });

            instance.on('change', function () {
                instance.updateElement();
                $input.trigger('change');
                return false;
            });

            instance.addCommand("show_more", {// create named command
                exec: function (edt) {
                    instance.destroy();
                    if ($input.data('preset') === 'full') {
                        initBasicEditor(id);
                    } else {
                        initFullEditor(id);
                    }
                }
            });

           /* instance.ui.addButton('show_more', {// add new button and bind our command
                label: buttonLabel,
                command: 'show_more',
                toolbar: 'showmore',
            }); */
        };

        $(document).off('click', '.cke_dialog_tabs a:visible:eq(2)').on('click', '.cke_dialog_tabs a:visible:eq(2)', function () {
            var $form = $('.cke_dialog_ui_input_file:visible iframe').contents().find('form');
            var csrfName = '<?= $csrfTokenName ?>';
            var token = '<?= $csrfToken ?>';

            if (!$form.find('input[name=' + csrfName + ']').length) {
                var csrfTokenInput = $('<input/>').attr({
                    'type': 'hidden',
                    'name': csrfName
                }).val(token);
                $form.append(csrfTokenInput);
            } else {
                $form.find('input[name=' + csrfName + ']').attr({
                    'type': 'hidden',
                    'name': csrfName
                }).val(token);
            }
        });

        initBasicEditor(id);
    })();
<?= Html::endTag('script') ?>
