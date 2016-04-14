<?php /* @var $this CmsGeneratorController */ ?>
<h1>Generate a new CMS component</h1>

<p>By clicking the link below, you will automatically generate a database patch containing a table creation request.</p>

<form action="componentGenerate" method="post" class="form-horizontal">
    <input type="hidden" id="name" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
    <input type="hidden" id="selfedit" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit) ?>"
    <div class="control-group">
        <label class="control-label">Component name :</label>
        <div class="controls">
            <input type="text" name="componentName">
            <span class="help-block">The name for the CMS component. This name will be the name of the table generated in the database patch.</span>
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <button name="action" value="componentGenerate" type="submit" class="btn btn-danger">Generate patch</button>
        </div>
    </div>
</form>