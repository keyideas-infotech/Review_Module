<!--<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />-->
<!--<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>-->
<?php
$name = "name_" . $this->config->item("language");
?>
<script>
    window.autoSuggestUrl = "<?php echo base_url("share_your_exp/auto_suggest_clubs"); ?>";
    window.clubinfo = "<?php echo base_url("share_your_exp/get_club_info"); ?>";
    window.imgurl = "<?php echo Template::theme_url("images/rating-hover.png"); ?>";
</script>
<?php if (isset($club_id)) : ?>
    <script type="text/javascript">
        $(function() {
            get_club_info_by_id(<?php echo $club_id; ?>);
            $("#name").attr({
                readonly: true
            });
        });
    </script>
<?php endif; ?>

<?php if ($this->input->post("name")) : ?>
    <script type="text/javascript">
        $(function() {
            $("#slider").slider("value", "<?php echo $this->input->post("percentage") ?>");
            $("#amount").val($("#slider").slider("value"));
    <?php if ($this->input->post("club_id")) : ?>
                get_club_info_by_id(<?php echo $this->input->post("club_id"); ?>);
    <?php endif; ?>
        });
    </script>
<?php endif; ?>



<?php
// echo "<pre>";
// print_r($this->input->post());
// echo "</pre>";
?>

<div class="innerMiddleContainer">
    <!--<div class="CommonHeading"><?php echo lang('share_your_exp_share_your_exp'); ?></div>-->
    <div class="form">
        <?php echo form_open_multipart(base_url("share_your_exp"), array('id'=>'share_exp_form')); ?>
        <input id="service" type="hidden" name="service" value=""/>
        <input id="ambiance" type="hidden" name="ambiance" value=""/>
        <input id="decoration" type="hidden" name="decoration" value=""/>
        <input id="price" type="hidden" name="price" value=""/>
        <input id="club_id" type="hidden" name="club_id" value=""/>
        <!--<input id="club_id" type="hidden" name="club_pictures" value=""/>-->
        <div class="message_div">
            <?php echo Template::message(); ?>
        </div>
        <div class="form-left-container">
            <div class="form-text">
                <div class="formleftcontent">
                    <div id="autosuggest" class="clubnameinner">
                        <input type="text" list="clubs" id="name" name="name" class="clubname" placeholder="<?php echo lang('share_your_exp_name_of_club') . lang('field_required'); ?>" size="" maxlength="255" value="<?php echo ($this->input->post()) ? set_value('name', $this->input->post("name")) : ""; ?>" autocomplete="on"  />
                        <span class="help-inline error"><?php echo form_error('name'); ?></span>
                    </div>
                    <div class="clubnameinner">
                        <input type="text" id="country" name="country" class="clubname" placeholder="<?php echo lang('share_your_exp_country') . lang('field_required'); ?>" size="" value="<?php echo set_value('country', $this->input->post("country")); ?>" />
                        <span class="help-inline error"><?php echo form_error('country'); ?></span>
                    </div>                    
                    <div class="clubnameinner">
                        <input type="text" id="city" name="city" class="clubname" placeholder="<?php echo lang('share_your_exp_city') . lang('field_required'); ?>" size="" value="<?php echo set_value('city', $this->input->post("city")); ?>" />
                        <span class="help-inline error"><?php echo form_error('city'); ?></span>
                    </div>                    
                    <div class="imageonly">
                        <?php echo lang('share_your_exp_add_picture'); ?>
                        <input type="file" id="club_images" name="club_images[]" multiple="true" class="imagesbtn" value="<?php echo lang('share_your_exp_add_picture'); ?>" size=""  />
                    </div>
                    <div>
                        <output id="list"></output>
                    </div>
                </div>
            </div>
            <div class="fashion">
                <div class="fsnstnd"><?php echo lang('share_your_exp_fashion_standing'); ?></div>
                <?php if (isset($fashion_standing) && $fashion_standing !== FALSE) : ?>
                    <div class="formcheck">
                        <?php foreach ($fashion_standing as $fashion) : ?>
                            <div class="check" ><input type="checkbox" class="fashion_standing" name="fashion_standing[]" value="<?php echo $fashion['id']; ?>" <?php echo set_checkbox('fashion_standing[]', $fashion['id']) ?> /> <div class="fdesp"><?php echo ucfirst($fashion['name']); ?></div></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="kind">
                <label class="kindclub"><?php echo lang('share_your_exp_kind_of_club'); ?></label>
            </div>
            <div class="formcheck">
                <?php if (isset($kind_of_club) && $kind_of_club !== FALSE) : ?>
                    <div class='controls'>
                        <?php foreach ($kind_of_club as $kind) : ?>
                            <div class="check2"><input type="checkbox" class="kind_of_club" name="kind_of_club[]" value="<?php echo $kind->id; ?>" <?php echo set_checkbox('kind_of_club[]', $kind->id) ?> /><div class="chek_d"> <?php echo ucfirst($kind->$name); ?></div></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-right-container">
            <div class="clubnameinner">
                <input type="text" name="title" id="title" class="clubname2" placeholder="<?php echo lang('share_your_exp_title'); ?>" size="" maxlength="255"  value="<?php echo set_value('title', $this->input->post("title")); ?>"  />
                <span class="help-inline error"><?php echo form_error('title'); ?></span>
            </div>
            <div class="formrightcontent">
                <textarea id="experience" name="experience" cols="" placeholder="<?php echo lang('share_your_exp_share_your_exp') . lang('field_required'); ?>" rows="" class="shareexp"><?php echo set_value('experience', $this->input->post("experience")); ?></textarea>
                <span class="help-inline error"><?php echo form_error('experience'); ?></span>
            </div>

            <div class="starright">
                <div class="starimg">
                    <ul>
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <li><span title="<?php echo $i; ?>" id="<?php echo $i; ?>" rel="service" class="icon_star"></span></li>
                        <?php endfor; ?>
                    </ul>
                    <span class="accservice"><?php echo lang('share_your_exp_service'); ?></span> </div>
                <div class="starimg">
                    <ul>
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <li><span title="<?php echo $i; ?>" id="<?php echo $i; ?>" rel="ambiance" class="icon_star"></span></li>
                        <?php endfor; ?>
                    </ul>
                    <span class="accservice"><?php echo lang('share_your_exp_ambiance'); ?></span> </div>
                <div class="starimg">
                    <ul>
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <li><span title="<?php echo $i; ?>" id="<?php echo $i; ?>" rel="decoration" class="icon_star"></span></li>
                        <?php endfor; ?>
                    </ul>
                    <span class="accservice"><?php echo lang('share_your_exp_decoration'); ?></span> </div>
                <div class="starimg">
                    <ul>
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <li><span title="<?php echo $i; ?>" id="<?php echo $i; ?>" rel="price" class="icon_star"></span></li>
                        <?php endfor; ?>
                    </ul>
                    <span class="accservice"><?php echo lang('share_your_exp_price'); ?></span> </div>
            </div>
            <div class="percentage"> 
                <div id="slider"></div>
                <br/>
                <div class="amount-desp">
                    <input class="span1" style="width: 25px;" type="text" id="amount" name="percentage" value="<?php echo set_value('percentage', $this->input->post("percentage")); ?>" readonly="true" /> <?php echo lang('share_your_exp_percentage'); ?> 
                    <span class="help-inline error"><?php echo form_error('percentage'); ?></span>
                </div>
                <br/>
            </div>

            <div class="share">
                <div class="shareit">
                    <input type="submit" value="<?php echo lang('share_your_exp_share_it'); ?>" class="shareitbutton_2" />
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>
        <div class="clr"></div>
    </div>
</div>
<script>



    function handleFileSelect(evt) {
        $("#list").empty();
        var files = evt.target.files; // FileList object
        // Loop through the FileList and render image files as thumbnails.


        for (var i = 0, f; f = files[i]; i++) {

            if (i > 11) {
                alert("you reached the maximum file upload limit of 12 photos.");
                break;
            }

            evt.target.files = files[i];
//            console.log(evt.target.files);    

            // Only process image files.
            if (!f.type.match('image.*')) {
                continue;
            }

            var reader = new FileReader();

            // Closure to capture the file information.
            reader.onload = (function(theFile) {
                return function(e) {
                    // Render thumbnail.
//                var span = document.createElement('span');
                    //change image Width as 155 to 115 :chiragPrajapati
                    var output = '<img width="115" height="155" class="thumb" src="' + e.target.result + '" title="' + escape(theFile.name) + '"/>';
//                    output += "<button id="+i+" onclick='removeFile($(this));'>remove</button>";
                    $("#list").append(output);
                };
            })(f);
            // Read in the image file as a data URL.
            reader.readAsDataURL(f);
        }
    }

    function removeFile(obj) {
        alert(obj.attr("id"));
        var elementobj = obj.attr("id");
//        alert(element);
//        console.log($("#club_images")[obj.attr("id")]);
//        alert($( "form :file" ));
        var files = document.getElementById('club_images').files;

        for (var i = 0; i < files.length; i++) {
            if (elementobj == i) {
                alert("in");
                continue;
            }
            document.getElementById('club_images').files = files[i];
            console.log(files[i]);
        }
        console.log("after");

        handleFileSelect(document.getElementById('club_images'));
    }
    document.getElementById('club_images').addEventListener('change', handleFileSelect, false);
</script>
