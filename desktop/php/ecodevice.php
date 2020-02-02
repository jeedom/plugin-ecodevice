<?php
if (! isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('ecodevice');
sendVarToJS('eqType', $plugin->getId());

//$eqLogicsCarte = eqLogic::byTypeAndSearhConfiguration('ecodevice', '"type":"carte"');

/** @var ecodevice[] $eqLogics */
$eqLogics = eqLogic::byType($plugin->getId());

/** @param ecodevice $eqL */
function displayEqLogicCard($eqL) {
    $opacity = ($eqL->getIsEnable()) ? '' : 'disableCard';
    echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqL->getId() . '">';
    echo '<img src="' . $eqL->getImage() . '"/>';
    echo '<br>';
    echo '<span class="name">' . $eqL->getHumanName(true, true) . '</span>';
    echo '</div>';
}
?>

<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i> <br> <span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i> <br> <span>{{Configuration}}</span>
      </div>
    </div>
    
    <?php
    foreach ($eqLogics as $eqLogic) {
        if ($eqLogic->getConfType() == ecodevice::TYP_CARTE) {
            echo '<legend><i class="fas fa-table"></i> Eco-device ' . $eqLogic->getName() . '</legend>';
            echo '<div class="eqLogicThumbnailContainer">';
            displayEqLogicCard($eqLogic);
            foreach ($eqLogics as $eqSubLogic) {
                if ($eqSubLogic->getCarteEqlogicId() == $eqLogic->getId()) {
                    displayEqLogicCard($eqSubLogic);
                }
            }
            echo '</div>';
        }
    }
    ?>
  </div>

  <div class="col-xs-12 eqLogic" style="display: none;">
    <div class="input-group pull-right" style="display: inline-flex">
      <span class="input-group-btn">
        <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
        <a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
        <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
        <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
      </span>
    </div>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height: calc(100% - 50px); overflow: auto; overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <br />
        <form class="form-horizontal">
          <fieldset>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display: none;" />
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="type" style="display: none;" />
                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Objet parent}}</label>
              <div class="col-sm-3">
                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                  <option value="">{{Aucun}}</option>
                        <?php
                        foreach (jeeObject::all() as $object) {
                            echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                        }
                        ?>
                   </select>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Catégorie}}</label>
              <div class="col-sm-9">
                 <?php
                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                    echo '<label class="checkbox-inline">';
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' .
                        $value['name'];
                    echo '</label>';
                }
                ?>
               </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label"></label>
              <div class="col-sm-9">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Accéder à l'interface de l'eco-devices}}</label>
              <div class="col-sm-3">
                <a class="btn btn-default" id="bt_goCarte" title='{{Accéder à la carte}}'><i class="fa fa-arrow-right"></i></a>
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{IP de l'eco-devices}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip" />
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Port de l'eco-devices}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="port" />
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Compte de l'eco-devices}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username" />
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Password de l'eco-devices}}</label>
              <div class="col-sm-3">
                <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" />
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Teleinfo 1}}</label>
              <div class="col-sm-3">
                <input type="checkbox" class="eqLogicAttr form-control meter" data-l1key="configuration" data-l2key="T1" />
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Teleinfo 2}}</label>
              <div class="col-sm-3">
                <input type="checkbox" class="eqLogicAttr form-control meter" data-l1key="configuration" data-l2key="T2" />
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Compteur 1}}</label>
              <div class="col-sm-3">
                <input type="checkbox" class="eqLogicAttr form-control meter" data-l1key="configuration" data-l2key="C0" />
              </div>
            </div>
            <div class="form-group carte_only">
              <label class="col-sm-3 control-label">{{Compteur 2}}</label>
              <div class="col-sm-3">
                <input type="checkbox" class="eqLogicAttr form-control meter" data-l1key="configuration" data-l2key="C1" />
              </div>
            </div>
            <div class="form-group compteur_only">
              <label class="col-sm-3 control-label" >{{Type}}</label>
              <div class="col-sm-3">
                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="typecompteur" id="typecompteur">
                  <option value="">{{Non défini}}</option>
                    <?php
                    foreach (ecodevice::getCompteurTypes() as $object) {
                        echo '<option value="' . $object . '">' . $object . '</option>';
                    }
                    ?>
                </select>
                <label class="inline" id="Alerte_Temps_de_fonctionnement">{{Mettre le compteur en mode fuel et 1 dans le débit du gicleur sur l'ecodevice.}}</label>
                <label class="inline" id="Alerte_Change_Type">{{Les indicateurs sont regénérés après sauvegarde.}}</label>
               </div>
            </div>
            <div class="form-group teleinfo_only">
              <label class="col-sm-3 control-label">{{Tarification}}</label>
              <div class="col-sm-3">
                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="tarification" id="tarification">
                  <option value="">Sans</option>
                  <option value="BASE">Base</option>
                  <option value="HC">Heure creuse/Heure pleine</option>
                  <option value="BBRH">Tempo</option>
                  <option value="EJP">EJP</option>
                </select>
                <label>{{Les indicateurs sont regénérés en cas de changement de tarif après sauvegarde.}}</label>
              </div>
            </div>
          </fieldset>
        </form>
      </div>
      <div role="tabpanel" class="tab-pane" id="commandtab">
        <a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top: 5px;"><i
          class="fa fa-plus-circle"></i> {{Commandes}}</a><br /> <br />
        <table id="table_cmd" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th>{{Nom}}</th>
              <th>{{Type}}</th>
              <th>{{Action}}</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include_file('desktop', 'ecodevice', 'js', 'ecodevice');?>
<?php include_file('core', 'plugin.template', 'js');?>
