<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('ecodevice');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br/>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br/>
				<span >{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-charging-station"></i> {{Mes Ecodevices}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br/>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>

  <div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default eqLogicAction btn-sm roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
        <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
        <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
				<form class="form-horizontal">
					<fieldset>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Nom de l'ecodevice}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'ecodevice}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                    <div class="col-sm-3">
                      <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
  											<option value="">{{Aucun}}</option>
  											<?php
  											$options = '';
  											foreach ((jeeObject::buildTree(null, false)) as $object) {
  												$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
  											}
  											echo $options;
  											?>
  										</select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Catégorie}}</label>
                    <div class="col-sm-6">
                        <?php
                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                            echo '<label class="checkbox-inline">';
                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                            echo '</label>';
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" ></label>
					<div class="col-sm-9">
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>Activer</label>
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>Visible</label>
					<a class="btn btn-default" id="bt_goCarte" title='{{Accéder à la carte}}'><i class="fa fa-cogs"></i></a>
					</div>
                </div>
                <div class="form-group carte_only">
                    <label class="col-sm-3 control-label">{{IP de l'ecodevice}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip"/>
                    </div>
                </div>
                <div class="form-group carte_only">
                    <label class="col-sm-3 control-label">{{Port de l'ecodevice}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="port"/>
                    </div>
                </div>
                <div class="form-group carte_only">
                    <label class="col-sm-3 control-label">{{Compte de l'ecodevice}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username"/>
                    </div>
                </div>
                <div class="form-group carte_only">
                    <label class="col-sm-3 control-label">{{Password de l'ecodevice}}</label>
                    <div class="col-sm-3">
                        <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password"/>
                    </div>
                </div>
                <div class="form-group compteur_only">
                    <label class="col-sm-3 control-label" >{{Type}}</label>
                    <div class="col-sm-3">
                        <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="typecompteur" id="typecompteur">
                            <option value="">{{Sans}}</option>
                            <?php
                            foreach (ecodevice::getTypeCompteur() as $object) {
                                echo '<option value="' . $object . '">' . $object . '</option>';
                            }
                            ?>
                        </select>
						<label class="inline" id="Alerte_Temps_de_fonctionnement">{{Mettre le compteur en mode fuel et 1 dans le débit du gicleur sur l'ecodevice.}}</label>
						<label class="inline" id="Alerte_Change_Type">{{Les indicateurs sont regénérés après sauvegarde.}}</label>
                    </div>
                </div>
				<div class="form-group teleinfo_only">
					<label class="col-sm-3 control-label">{{Tarification :}}</label>
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
    <br/>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th>{{Nom}}</th>
                    <th>{{Type}}</th>
                    <th>{{Paramètres}}</th>
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

<?php include_file('core', 'plugin.template', 'js'); ?>
<?php include_file('desktop', 'ecodevice', 'js', 'ecodevice'); ?>
<script type="text/javascript">
if (getUrlVars('saveSuccessFull') == 1) {
    $('#div_alert').showAlert({message: '{{Sauvegarde effectuée avec succès}}<br>{{Utilisez l\icône suivant pour voir le détail de l\'élément <i class="fa fa-sitemap"></i>}}', level: 'success'});
}
</script>
