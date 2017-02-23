<div id="nav-col">
  <section id="col-left" class="col-left-nano">
    <div id="col-left-inner" class="col-left-nano-content">
      <div class="collapse navbar-collapse navbar-ex1-collapse" id="sidebar-nav">
        <ul class="nav nav-pills nav-stacked">
          <li <?if ($active_db==1){echo "class='active'";}?>>
            <a href="index.php">
              <i class="fa fa-dashboard"></i>
              <span>Home</span>
            </a>
          </li>
          <li <?if ($active_p==1){echo "class='active'";}?>>
            <a href="post.php">
              <i class="fa fa-folder-open"></i>
              <span>Post</span>
            </a>
          </li>
        
          <li <?if ($active_shop==1){echo "class='active'";}?>>
            <a href="backlog.php">
              <i class="fa fa-pencil-square-o"></i>
              <span>Backlog</span>
            </a>
          </li>  
        </ul>
      </div>
    </div>
  </section>
  <div id="nav-col-submenu"></div>
</div>