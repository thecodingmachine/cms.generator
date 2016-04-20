<?php /* @var $this CmsGeneratorController */ ?>
<h1>Generate a new CMS component</h1>

<p>By clicking the link below, you will automatically generate a CMS component with database patch, Dao, Bean, views, URL and controller.</p>

<form action="componentGenerate" method="post" class="form-horizontal">
    <input type="hidden" id="name" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
    <input type="hidden" id="selfedit" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit) ?>"
    <div class="control-group">
        <label class="control-label">Component name :</label>
        <div class="controls">
            <input type="text" name="componentName">
            <span class="help-block">The name for the CMS component.</span>
        </div>
        <div class="controls">
            <button name="action" value="componentGenerate" type="submit" class="btn btn-primary">Generate component</button>
        </div>
    </div>
</form>