<?php
// News admin page - uses NewsAdminController for MVC pattern
require __DIR__ . '/db.php';
require_once __DIR__ . '/../../controller/NewsAdminController.php';
require_once __DIR__ . '/../../model/CommentRepository.php';
require_once __DIR__ . '/../../model/helpers.php';

// Create directories for uploads if they don't exist
$uploadsDir = __DIR__ . '/uploads/images';
$historyDir = __DIR__ . '/uploads/history';
$commentsDir = __DIR__ . '/uploads/comments';
@mkdir($uploadsDir, 0755, true);
@mkdir($historyDir, 0755, true);
@mkdir($commentsDir, 0755, true);

// Comment moderation is now handled through the database
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>News Admin - Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
  <div class="stars"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>

  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php" class="">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="#">Events</a>
    <a href="news_admin.php" class="active">News</a>
    <a href="news_history.php" id="news-history-link">News History</a>
    <a href="categories.php" id="categories-link">Categories</a>
    <a href="#">Support</a>
    <a href="../front/indexf.php">← Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>News Administration</h1>
      <div class="user">
        <img src="../images/rayen.png" alt="User Avatar">
        <span>FoxAdmin</span>
      </div>
    </div>

    <div class="content">
      <div class="card" style="width:100%; grid-column: 1 / -1;">
      
        <div style="margin-bottom:16px;border-bottom:1px solid #333;padding-bottom:12px">
          <span style="font-weight:700;color:#fff;font-size:1.05rem">Manage News</span>
        </div>

        <div id="tab-manage-content">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <h2 style="margin:0">Manage News</h2>
              <div>
              <a class="btn" href="news_admin.php?action=new">+ Add Article</a>
              <a class="btn" href="../front/news.php" target="_blank">View Public News</a>
            </div>
          </div>

              <?php if (!empty($messages) || !empty($errors)): ?>
                <div style="margin-top:10px">
                  <?php foreach ($messages as $m): ?><div style="color:#b6ffb3;padding:8px;border-radius:6px;background:#0b2b10;margin-bottom:6px"><?php echo htmlspecialchars($m); ?></div><?php endforeach; ?>
                  <?php foreach ($errors as $e): ?><div style="color:#ffd6d6;padding:8px;border-radius:6px;background:#2b0b0b;margin-bottom:6px"><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
                </div>
              <?php endif; ?>

          <?php if ($action === 'new' || $editing !== null): ?>
            <?php $it = $editing ?? ['id'=>'','title'=>'','date'=>date('Y-m-d'),'datePublication'=>date('Y-m-d'),'image'=>'','idCategorie'=>0,'category'=>'','excerpt'=>'','content'=>'','hot'=>0]; ?>
            <section style="margin-top:16px">
              <h3><?php echo $editing ? 'Edit' : 'New'; ?> Article</h3>
              <form id="article-form" class="admin-form" method="post" action="news_admin.php<?php echo $editing ? '?id='.urlencode($editing['id']) : '' ;?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editing ? 'save' : 'add'; ?>">
                <?php if (!$editing): ?><label for="fld-id">ID (alphanumeric):</label><input id="fld-id" name="id" value="<?php echo htmlspecialchars($it['id'] ?? ''); ?>" class="small"><?php endif; ?>
                <label for="fld-datePublication">Publication Date</label><input id="fld-datePublication" name="datePublication" type="date" value="<?php echo htmlspecialchars($it['datePublication'] ?? date('Y-m-d')); ?>" class="small">
                <label for="fld-title">Title</label><input id="fld-title" name="title" value="<?php echo htmlspecialchars($it['title'] ?? ''); ?>">
                <label for="fld-date">Displayed Date</label><input id="fld-date" name="date" type="date" value="<?php echo htmlspecialchars($it['date'] ?? date('d-m-Y')); ?>" class="small">
                
                <label for="fld-image-upload">Upload Image</label>
                <input id="fld-image-upload" type="file" name="image_upload" accept="image/*" style="padding:8px;margin:8px 0;background:#0b0b0b;border:1px solid #333;color:#fff;border-radius:6px;width:100%">
                <input type="hidden" id="fld-image-existing" name="image_existing" value="<?php echo htmlspecialchars($it['image'] ?? ''); ?>">
                <?php if (!empty($it['image'])): ?>
                  <div style="margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333">
                    <img id="img-preview" src="<?php echo htmlspecialchars($it['image']); ?>" style="max-width:100%;max-height:200px;display:block" alt="Preview">
                  </div>
                <?php else: ?>
                  <div id="img-preview-container" style="display:none;margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333">
                    <img id="img-preview" style="max-width:100%;max-height:200px;display:block" alt="Preview">
                  </div>
                <?php endif; ?>
                
                <label for="fld-category">Category</label>
                <select id="fld-idCategorie" name="idCategorie">
                  <option value="0">-- Select category --</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?php echo intval($c['idCategorie']); ?>" <?php if (!empty($it['idCategorie']) && intval($it['idCategorie'])===intval($c['idCategorie'])) echo 'selected'; ?>><?php echo htmlspecialchars($c['nom']); ?></option>
                  <?php endforeach; ?>
                </select>
                <small style="display:block;color:#aaa;margin-top:6px">Or enter a custom category name below (will be saved to article only):</small>
                <input id="fld-category" name="category" value="<?php echo htmlspecialchars($it['category'] ?? ''); ?>">
                
                <div style="margin:16px 0">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="fld-hot" name="hot" value="1" <?php echo ($it['hot'] ?? 0) ? 'checked' : ''; ?> style="margin:0">
                    <span>🔥 Mark as Hot News (will appear at top of news page)</span>
                  </label>
                </div>
                <label for="fld-excerpt">Excerpt / Summary</label><textarea id="fld-excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($it['excerpt'] ?? ''); ?></textarea>
                <label for="fld-content">Full Content</label><textarea id="fld-content" name="content" rows="8"><?php echo htmlspecialchars($it['content'] ?? ''); ?></textarea>

                <?php if ($editing): ?>
                <div style="margin-top:24px;background:#0b0b0b;padding:12px;border-radius:8px;border-left:3px solid #ff7a00">
                  <h3 style="margin-top:0;color:#ff7a00">Comments for: <?php echo htmlspecialchars($editing['id']); ?></h3>
                  <?php
                  $slug = $editing['id'];
                  $commentRepository = new CommentRepository($pdo);
                  $comments = $commentRepository->findCommentsByArticleId($editing['idArticle']);

                  if (empty($comments)) {
                    echo '<p style="color:#bbb">No comments for this article.</p>';
                  } else {
                    foreach ($comments as $i => $comment) {
                      $name = htmlspecialchars($comment->getName());
                      $date = htmlspecialchars($comment->getCreatedAt()->format('Y-m-d H:i:s'));
                      $text = htmlspecialchars($comment->getText());

                      echo '<form method="post" action="news_admin.php" style="background:#111;padding:10px;border-radius:6px;margin-bottom:10px">';
                      echo '<input type="hidden" name="action" value="update_comment">';
                      echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
                      echo '<input type="hidden" name="index" value="' . intval($i) . '">';
                      echo '<div style="display:flex;gap:8px;align-items:flex-start">';
                      echo '<div style="flex:1">';
                      echo '<label style="color:#ccc;display:block;margin-bottom:6px">Name</label>';
                      echo '<input name="name" value="' . $name . '" style="width:100%;padding:8px;margin-bottom:8px;background:#0b0b0b;border:1px solid #333;color:#fff;border-radius:6px">';
                      echo '<label style="color:#ccc;display:block;margin-bottom:6px">Comment</label>';
                      echo '<textarea name="text" rows="4" style="width:100%;padding:8px;background:#0b0b0b;border:1px solid #333;color:#fff;border-radius:6px">' . $text . '</textarea>';
                      echo '<div style="margin-top:8px">';
                      echo '<button class="btn" type="submit">Save</button> ';
                      echo '</div>';
                      echo '</div>';
                      echo '<div style="width:140px;text-align:right;">';
                      echo '<div style="color:#999;font-size:0.85rem;margin-bottom:12px">' . $date . '</div>';
                      echo '</div>';
                      echo '</div>';
                      echo '</form>';
                    }
                    // Comment deletion functionality removed - only editing allowed
                  }
                  ?>
 
                  <h3>Edit History</h3>
                  <?php
                  $history = $editing['history'] ?? [];
                  if (empty($history)) {
                    echo '<p style="color:#bbb">No edit history for this article.</p>';
                  } else {
                    foreach ($history as $h) {
                      echo '<div style="background:#111;padding:10px;border-radius:6px;margin-bottom:10px">';
                      echo '<div style="color:#999;font-size:0.85rem;margin-bottom:8px">Edited by ' . htmlspecialchars($h['edited_by_name'] ?? 'Unknown') . ' on ' . htmlspecialchars($h['edited_at']) . '</div>';
                      echo '<strong>' . htmlspecialchars($h['titre']) . '</strong><br>';
                      echo '<small style="color:#bbb">Excerpt: ' . htmlspecialchars(substr($h['excerpt'] ?? '', 0, 100)) . '...</small>';
                      echo '</div>';
                    }
                  }
                  ?>
                </div>
                <?php endif; ?>

                <p>
                  <button id="btn-save" class="btn" type="submit">Save</button>
                  <a class="btn" href="news_admin.php">Cancel</a>
                  <button id="btn-restore" class="btn" type="button" style="margin-left:8px;">Restore Draft</button>
                  <button id="btn-clear-draft" class="btn" type="button" style="margin-left:6px;background:#c33;color:#fff;border-color:#c33;">Clear Draft</button>
                </p>
              </form>
            </section>
          <?php else: ?>

            <section style="margin-top:16px">
              <h3>Existing Articles (<?php echo count($data); ?>)</h3>
              <table class="admin-table" id="articles-table">
                <thead><tr><th>#</th><th>Slug</th><th>Title</th><th>Date</th><th>Category</th><th>Hot</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($data as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['idArticle'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['date'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars((findCategoryName($row['idCategorie'] ?? 0, $categories) ?? '') . (empty($row['idCategorie']) ? '' : ' (ID:'. ($row['idCategorie']) .')')); ?></td>
                    <td><?php echo $row['hot'] ? '🔥 Yes' : 'No'; ?></td>
                    <td class="admin-actions">
                      <a href="news_admin.php?action=edit&id=<?php echo urlencode($row['id']); ?>">Edit</a> |
                      <a href="news_admin.php?action=delete&id=<?php echo urlencode($row['id']); ?>" onclick="return confirm('Delete this article?');">Delete</a>
                      <?php if ($row['hot']): ?>
                        | <a href="news_admin.php" onclick="toggleHot('<?php echo urlencode($row['id']); ?>'); return false;" style="color:#ff7a00">🔥 Hot</a>
                      <?php else: ?>
                        | <a href="news_admin.php" onclick="toggleHot('<?php echo urlencode($row['id']); ?>'); return false;" style="color:#888">🔥 Make Hot</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </section>

            <?php if ($action === 'delete' && $id !== ''): ?>
              <section style="margin-top:12px">
                <h3>Confirm Delete</h3>
                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($id); ?></strong>?</p>
                <form class="admin-form" method="post" action="news_admin.php?id=<?php echo urlencode($id); ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="btn" type="submit">Yes, delete</button>
                  <a class="btn" href="news_admin.php">Cancel</a>
                </form>
              </section>
            <?php endif; ?>


          <?php endif; ?>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <div class="transition-screen"></div>
  <script>
    // Ensure the transition overlay is hidden after page load
    window.addEventListener('load', function(){
      try{
        var t = document.querySelector('.transition-screen');
        if(t) t.classList.add('hidden');
      }catch(e){}
    });
  </script>
  <div class="toast-container" id="toast-container"></div>
  
  <style>
    .tab-btn {
      background: transparent;
      border: 1px solid #333;
      color: #bbb;
      padding: 10px 16px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s ease;
      font-weight: 600;
    }
    .tab-btn.active {
      background: #ff7a00;
      color: #000;
      border-color: #ff7a00;
    }
    .tab-btn:hover {
      border-color: #ff7a00;
    }
  </style>

  <!-- TinyMCE removed - using plain textarea for content -->
  <script>
    // Tab switching - ensure it works properly
    document.addEventListener('DOMContentLoaded', function(){
      console.log('DOM loaded, setting up tabs');
      
      var tabManageBtn = document.getElementById('tab-manage');
      var tabHistoryBtn = document.getElementById('tab-history');
      var tabCommentsBtn = document.getElementById('tab-comments');
      var tabManageContent = document.getElementById('tab-manage-content');
      var tabHistoryContent = document.getElementById('tab-history-content');
      var tabCommentsContent = document.getElementById('tab-comments-content');
      
      console.log('Elements found:', {
        tabManageBtn: !!tabManageBtn,
        tabHistoryBtn: !!tabHistoryBtn,
        tabManageContent: !!tabManageContent,
        tabHistoryContent: !!tabHistoryContent
      });
      
      if (tabManageBtn && tabHistoryBtn && tabManageContent && tabHistoryContent && tabCommentsBtn && tabCommentsContent) {
        tabManageBtn.addEventListener('click', function(e){
          e.preventDefault();
          console.log('Manage tab clicked');
          tabManageBtn.classList.add('active');
          tabHistoryBtn.classList.remove('active');
          tabCommentsBtn.classList.remove('active');
          tabManageContent.style.display = 'block';
          tabHistoryContent.style.display = 'none';
          tabCommentsContent.style.display = 'none';
        });
        
        tabHistoryBtn.addEventListener('click', function(e){
          e.preventDefault();
          console.log('History tab clicked');
          tabHistoryBtn.classList.add('active');
          tabManageBtn.classList.remove('active');
          tabCommentsBtn.classList.remove('active');
          tabHistoryContent.style.display = 'block';
          tabManageContent.style.display = 'none';
          tabCommentsContent.style.display = 'none';
        });
        
        tabCommentsBtn.addEventListener('click', function(e){
          e.preventDefault();
          tabCommentsBtn.classList.add('active');
          tabManageBtn.classList.remove('active');
          tabHistoryBtn.classList.remove('active');
          tabCommentsContent.style.display = 'block';
          tabManageContent.style.display = 'none';
          tabHistoryContent.style.display = 'none';
        });
      }
    });

    // Image file preview
    var fileInput = document.getElementById('fld-image-upload');
    if (fileInput) {
      fileInput.addEventListener('change', function(e){
        var file = e.target.files[0];
        if (file) {
          var reader = new FileReader();
          reader.onload = function(ev){
            var existingImg = document.getElementById('img-preview');
            if (!existingImg) {
              var container = document.getElementById('img-preview-container');
              if (!container) {
                container = document.createElement('div');
                container.id = 'img-preview-container';
                container.style.cssText = 'margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333';
                fileInput.parentNode.appendChild(container);
              }
              existingImg = document.createElement('img');
              existingImg.id = 'img-preview';
              existingImg.style.cssText = 'max-width:100%;max-height:200px;display:block';
              container.appendChild(existingImg);
            }
            if (existingImg.parentNode && existingImg.parentNode.id === 'img-preview-container') {
              existingImg.parentNode.style.display = 'block';
            } else if (!existingImg.parentNode || existingImg.parentNode.tagName === 'BODY') {
              var container = document.getElementById('img-preview-container');
              if (!container) {
                container = document.createElement('div');
                container.id = 'img-preview-container';
                container.style.cssText = 'margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333';
                fileInput.parentNode.appendChild(container);
              }
              container.appendChild(existingImg);
              container.style.display = 'block';
            }
            existingImg.src = ev.target.result;
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // Client-side: TinyMCE editor, autosave/restore drafts, toast handling, and validation
    (function(){
      function toast(message, type){
        var container = document.getElementById('toast-container');
        if(!container) return;
        var el = document.createElement('div'); el.className = 'toast ' + (type||''); el.textContent = message;
        container.appendChild(el);
        setTimeout(function(){ el.style.opacity = '1'; }, 10);
        setTimeout(function(){ el.style.opacity = '0'; setTimeout(function(){ try{ container.removeChild(el); }catch(e){} },400); }, 4500);
      }

      var serverMessages = <?php echo json_encode($messages); ?> || [];
      var serverErrors = <?php echo json_encode($errors); ?> || [];
      var currentId = <?php echo json_encode($editing['id'] ?? 'new'); ?>;

      document.addEventListener('DOMContentLoaded', function(){
        console.log('DOMContentLoaded fired, currentId:', currentId);
        console.log('Server messages:', serverMessages);
        console.log('Server errors:', serverErrors);
        serverMessages.forEach(function(m){ if(m) toast(m,''); });
        serverErrors.forEach(function(e){ if(e) toast(e,'error'); });

        // Attach button event listeners after DOM is ready
        var btnRestore = document.getElementById('btn-restore');
        var btnClear = document.getElementById('btn-clear-draft');
        console.log('Buttons found after DOM ready - Restore:', !!btnRestore, 'Clear:', !!btnClear);

        if(btnRestore){
          console.log('Attaching restore button listener');
          btnRestore.addEventListener('click', function(){
            console.log('Restore button clicked');
            try{
              var key = 'news_draft_' + currentId;
              console.log('Looking for draft with key:', key);
              var raw = localStorage.getItem(key);
              console.log('Raw draft data:', raw);
              if(!raw){
                console.log('No draft found');
                toast('No draft found','error');
                return;
              }
              var d = JSON.parse(raw);
              console.log('Parsed draft data:', d);
              if(document.getElementById('fld-title')) document.getElementById('fld-title').value = d.title || '';
              if(document.getElementById('fld-date')) document.getElementById('fld-date').value = d.date || '';
              if(document.getElementById('fld-datePublication')) document.getElementById('fld-datePublication').value = d.datePublication || '';
              if(document.getElementById('fld-image-existing')) document.getElementById('fld-image-existing').value = d.image || '';
              if(document.getElementById('fld-idCategorie')) document.getElementById('fld-idCategorie').value = d.idCategorie || '';
              if(document.getElementById('fld-category')) document.getElementById('fld-category').value = d.category || '';
              if(document.getElementById('fld-hot')) document.getElementById('fld-hot').checked = (d.hot === '1');
              if(document.getElementById('fld-excerpt')) document.getElementById('fld-excerpt').value = d.excerpt || '';
              if(document.getElementById('fld-content')) document.getElementById('fld-content').value = d.content || '';
              toast('Draft restored');
            }catch(e){
              console.error('Restore failed:', e);
              toast('Failed to restore draft','error');
            }
          });
        } else {
          console.error('Restore button not found!');
        }

        if(btnClear){
          console.log('Attaching clear button listener');
          btnClear.addEventListener('click', function(){
            console.log('Clear button clicked');
            try{
              var key = 'news_draft_' + currentId;
              console.log('Clearing draft with key:', key);
              localStorage.removeItem(key);
              toast('Draft cleared');
            }catch(e){
              console.error('Clear failed:', e);
              toast('Failed to clear draft','error');
            }
          });
        } else {
          console.error('Clear button not found!');
        }
      });

      // Autosave
      var autosaveInterval = 5000; // ms
      var autosaveTimer = null;
      function scheduleAutosave(){ if(autosaveTimer) clearTimeout(autosaveTimer); autosaveTimer = setTimeout(doAutosave, autosaveInterval); }
      function doAutosave(){ try{
        var key = 'news_draft_' + currentId;
        var payload = {
          title: (document.getElementById('fld-title')||{}).value || '',
          date: (document.getElementById('fld-date')||{}).value || '',
          datePublication: (document.getElementById('fld-datePublication')||{}).value || '',
          image: (document.getElementById('fld-image-existing')||{}).value || '',
          idCategorie: (document.getElementById('fld-idCategorie')||{}).value || '',
          category: (document.getElementById('fld-category')||{}).value || '',
          hot: (document.getElementById('fld-hot')||{}).checked ? '1' : '0',
          excerpt: (document.getElementById('fld-excerpt')||{}).value || '',
          content: (document.getElementById('fld-content')||{}).value || '',
          timestamp: Date.now()
        };
        localStorage.setItem(key, JSON.stringify(payload));
        console.log('Draft saved:', key, payload);
      }catch(e){ console.error('Autosave failed:', e); }

      // Schedule autosave on input/change
      ['fld-title','fld-date','fld-datePublication','fld-excerpt','fld-content','fld-category'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) {
          el.addEventListener('input', function(){ console.log('Input event on', id); scheduleAutosave(); });
        } else {
          console.warn('Element not found:', id);
        }
      });
      // For select and checkbox
      ['fld-idCategorie'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) {
          el.addEventListener('change', function(){ console.log('Change event on', id); scheduleAutosave(); });
        } else {
          console.warn('Element not found:', id);
        }
      });
      ['fld-hot'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) {
          el.addEventListener('change', function(){ console.log('Change event on', id); scheduleAutosave(); });
        } else {
          console.warn('Element not found:', id);
        }
      });

      // Restore / Clear buttons are attached in DOMContentLoaded below

      // Clear draft if server reports success
      document.addEventListener('DOMContentLoaded', function(){
        console.log('Clearing draft on success check - serverMessages:', serverMessages);
        if(serverMessages && serverMessages.length){
          var keys = ['added successfully','updated successfully','deleted'];
          var clear = serverMessages.some(function(m){ m = (m||'').toLowerCase(); return keys.some(function(k){ return m.indexOf(k)!==-1; }); });
          console.log('Should clear draft:', clear);
          if(clear){ try{ localStorage.removeItem('news_draft_' + currentId); console.log('Draft cleared after success'); }catch(e){} }
        }
      });

      // Validation on submit
      var form = document.getElementById('article-form');
      console.log('Form found:', !!form);
      if(form){
        console.log('Attaching form submit listener');
        form.addEventListener('submit', function(e){
          console.log('Form submit triggered');
          var isEdit = <?php echo $editing ? 'true' : 'false'; ?>;
          console.log('isEdit:', isEdit);
          var idField = document.getElementById('fld-id');
          var title = document.getElementById('fld-title');
          console.log('idField:', !!idField, 'title:', !!title);
          var errors = [];
          if(!isEdit){
            if(!idField || !idField.value.trim()) {
              errors.push('ID is required for new articles.');
            } else if(!/^[a-z0-9_-]+$/i.test(idField.value.trim())) {
              errors.push('ID may contain only letters, numbers, underscore and hyphen.');
            }
          }
          if(!title || !title.value.trim()) {
            errors.push('Title is required.');
          }
          console.log('Validation errors:', errors);
          if(errors.length){
            e.preventDefault();
            errors.forEach(function(m){ toast(m,'error'); });
            return false;
          }
          console.log('Form validation passed, allowing submit');
        });
      } else {
        console.error('Form not found!');
      }

      // Toggle hot status
      window.toggleHot = function(articleId) {
        var formData = new FormData();
        formData.append('action', 'toggle_hot');
        formData.append('id', articleId);
        
        fetch('news_admin.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(html => {
          // Reload page to show updated status
          window.location.reload();
        })
        .catch(error => {
          toast('Failed to update hot status', 'error');
        });
      };
    })();
  </script>
</body>
</html>