(async function () {
  const KB_ACTIONS_BASE = "actions";
  const titleEl = document.getElementById("post-title-placeholder");
  const breadcrumbsEl = document.getElementById("post-breadcrumbs");
  const contentEl = document.getElementById("post-full-content");
  const repliesEl = document.getElementById("post-replies-list");
  const replyForm = document.getElementById("reply-form");

  const params = new URLSearchParams(window.location.search);
  const postId = params.get("post_id");

  if (!postId) {
    titleEl.textContent = "Post not found";
    breadcrumbsEl.textContent = "Knowledge Base > Post";
    contentEl.innerHTML = `<p style="margin-top:16px;">Missing post_id in the URL.</p>`;
    return;
  }

  let currentUser = null;
  let post = null;

  try {
    // 0) Fetch current logged-in user
    currentUser = await getCurrentUser();

    // 1) Fetch post
    const postRes = await fetch(`actions/fetch_post.php?post_id=${encodeURIComponent(postId)}`, {
      credentials: "include",
    });
    const postData = await postRes.json();

    if (!postData.success) {
      titleEl.textContent = "Post not found";
      contentEl.innerHTML = `<p style="margin-top:16px;">${escapeHtml(
        postData.message || "Could not load post."
      )}</p>`;
      return;
    }

    post = postData.post || {};

    // Page title + breadcrumbs
    titleEl.textContent = post.title || "Untitled";

    // If fetch_post.php returns topic_name use otherwise fallback.
    const topicName = post.topic_name || "Post";
    renderRelatedTopics({ currentTopicName: topicName, currentUser });
    breadcrumbsEl.innerHTML = `
      <a href="knowledge-base.html">Knowledge Base</a> >
      <span>${escapeHtml(topicName)}</span> >
      <span>Post</span>
    `;

    // Render the post
    const postAvatarSrc = post.profile_picture || '../../default-avatar.png';

    const isSolved = Number(post.is_solved || 0) === 1;

    const isManager = String(currentUser?.role || "").toLowerCase() === "manager";

    const solvedBadgeHtml = isSolved
      ? `<span class="kb-solved-badge">Solved</span>`
      : "";

    const markSolvedBtnHtml = (!isSolved && isManager)
      ? `<button type="button" class="kb-solve-btn" id="kb-mark-solved" data-post-id="${post.post_id}">
          Mark as solved
        </button>`
      : "";


    contentEl.innerHTML = `
      <div class="post-card">
        <div class="post-card-header">
          <img class="post-card-avatar" src="${postAvatarSrc}" alt="${escapeHtml(post.author_name || 'Unknown')}" onerror="this.src='../../default-avatar.png'">
          <div>
            <span class="post-card-author">${escapeHtml(post.author_name || "Unknown")}</span>
            <span class="post-card-date">${formatDate(post.created_at)}</span>
          </div>
        </div>

        <div class="post-card-body">
          <p>${formatMultiline(post.content || "")}</p>
        </div>

        <div class="post-card-footer">
          <button class="kb-like-btn${post.user_has_liked ? ' liked' : ''}" data-post-id="${post.post_id}">
            <i data-feather="thumbs-up"></i>
            <span class="kb-like-count">${post.like_count || 0}</span>
          </button>

          <span><i data-feather="message-circle"></i> ${post.comment_count}</span>

          ${solvedBadgeHtml}
          ${markSolvedBtnHtml}
        </div>


      </div>
    `;

    applyLastEditedText(post);
    addEditDeleteControlsIfAllowed(currentUser, post);

    wireSinglePostLike();
    wireMarkSolved();

    // 2) Fetch comments for this post
    const comRes = await fetch(`actions/fetch_comments.php?post_id=${encodeURIComponent(postId)}`, {
      credentials: "include",
    });
    const comData = await comRes.json();

    if (!comData.success) {
      repliesEl.innerHTML = `<p style="margin-top:10px;">Could not load replies.</p>`;
    } else {
      renderReplies(comData.comments || []);
      wireCommentActionButtons(); 
    }


    // Show reply form
    if (replyForm) replyForm.style.display = "block";
    wireReplyForm();

    function wireReplyForm() {
      if (!replyForm) return;

      replyForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const textarea = document.getElementById("reply-content");
        const text = String(textarea?.value || "").trim();
        if (!text) {
          alert("Reply cannot be empty.");
          return;
        }

        try {
          const fd = new FormData();
          fd.append("post_id", String(postId));
          fd.append("content", text);

          const res = await fetch("actions/add_comment.php", {
            method: "POST",
            body: fd,
            credentials: "include",
          });

          const data = await res.json();
          if (!data.success) {
            alert(data.message || "Could not add reply.");
            return;
          }

          // Clear textarea
          textarea.value = "";

          // Reload comments so it shows instantly
          const comRes = await fetch(`actions/fetch_comments.php?post_id=${encodeURIComponent(postId)}`, {
            credentials: "include",
          });
          const comData = await comRes.json();

          if (comData.success) {
            renderReplies(comData.comments || []);
            wireCommentActionButtons();
            feather.replace();
          }
        } catch (err) {
          console.error(err);
          alert("Something went wrong posting your reply.");
        }
      });
    }


    feather.replace(); 
  } catch (err) {
    console.error(err);
    titleEl.textContent = "Error loading post";
    contentEl.innerHTML = `<p style="margin-top:16px;">Something went wrong loading this post.</p>`;
  }

  function renderReplies(comments) {
    if (!comments.length) {
      repliesEl.innerHTML = `<p style="margin-top:10px;">No replies yet.</p>`;
      return;
    }

    repliesEl.innerHTML = comments
      .map((c) => {
        const replyAvatarSrc = c.profile_picture || '../../default-avatar.png';

        const specialistTag =
          String(c.author_role || "").toLowerCase() === "specialist" ? " (Specialist)" : "";

        return `
          <div class="reply-card" data-comment-id="${Number(c.comment_id || 0)}">
            <img class="reply-avatar" src="${replyAvatarSrc}" alt="${escapeHtml(c.author_name || 'Unknown')}" onerror="this.src='../../default-avatar.png'">

            <div class="reply-content">
              <div class="reply-header">
                <span class="reply-author">${escapeHtml(c.author_name || "Unknown")}${specialistTag}</span>
                <span class="reply-date">
                  ${formatDate(c.created_at)}
                  ${c.last_edited_at ? ` • Edited ${formatDate(c.last_edited_at, true)}` : ""}
                </span>

                ${renderCommentActions(c)}
              </div>

              <div class="reply-body">
                <p>${formatMultiline(c.content || "")}</p>
              </div>
            </div>
          </div>
        `;

      })
      .join("");
  }


  function renderCommentActions(comment) {
    if (!currentUser) return "";

    const isAuthor  = String(currentUser.user_id) === String(comment.author_id);
    const isManager = String(currentUser.role || "").toLowerCase() === "manager";

    const canEdit = isAuthor;
    const canDelete = isAuthor || isManager;

    if (!canEdit && !canDelete) return "";

    return `
      <div class="post-actions" style="margin-left:auto;">
        ${canEdit ? `<button type="button" class="post-action-btn post-action-btn--edit" data-action="edit-comment">Edit</button>` : ""}
        ${canDelete ? `<button type="button" class="post-action-btn post-action-btn--delete" data-action="delete-comment">Delete</button>` : ""}
      </div>
    `;
  }

  function wireCommentActionButtons() {
    // Event delegation on the replies container
    if (!repliesEl) return;

    repliesEl.onclick = async (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;

      const card = btn.closest(".reply-card");
      const commentId = Number(card?.dataset?.commentId || 0);
      if (!commentId) return;

      const action = btn.dataset.action;

      if (action === "edit-comment") {
        openInlineCommentEdit(card, commentId);
      }

      if (action === "delete-comment") {
        await deleteCommentFlow(commentId);
      }
    };
  }

  function openInlineCommentEdit(cardEl, commentId) {
    const body = cardEl.querySelector(".reply-body");
    const p = body?.querySelector("p");
    if (!body || !p) return;

    // prevent double-open
    if (body.querySelector(".edit-comment-inline")) return;

    const originalHTML = body.innerHTML;
    const existingText = p.innerText; // keeps line breaks

    body.innerHTML = `
      <div class="edit-comment-inline">
        <textarea id="edit-comment-text" rows="4" style="width:100%; border-radius:8px; border:1px solid #EAECEE; padding:12px 15px; font-family:'Poppins',sans-serif; font-size:14px; resize:vertical;">${escapeHtml(existingText)}</textarea>
        <div class="edit-actions">
          <button type="button" class="create-post-btn" id="save-comment-edit">Save</button>
          <button type="button" class="edit-cancel-btn" id="cancel-comment-edit">Cancel</button>
        </div>
      </div>
    `;

    const textarea = body.querySelector("#edit-comment-text");
    const saveBtn = body.querySelector("#save-comment-edit");
    const cancelBtn = body.querySelector("#cancel-comment-edit");

    cancelBtn.addEventListener("click", () => {
      body.innerHTML = originalHTML;
    });

    saveBtn.addEventListener("click", async () => {
      const newText = String(textarea.value || "").trim();
      if (!newText) {
        alert("Reply cannot be empty.");
        return;
      }

      try {
        const fd = new FormData();
        fd.append("comment_id", String(commentId));
        fd.append("content", newText);

        const res = await fetch("actions/update_comment.php", {
          method: "POST",
          body: fd,
          credentials: "include",
        });

        const data = await res.json();
        if (!data.success) {
          alert(data.message || "Could not update reply.");
          return;
        }

        // refresh comments list
        const comRes = await fetch(`actions/fetch_comments.php?post_id=${encodeURIComponent(postId)}`, {
          credentials: "include",
        });
        const comData = await comRes.json();
        if (comData.success) {
          renderReplies(comData.comments || []);
          wireCommentActionButtons();
          feather.replace();
        }
      } catch (err) {
        console.error(err);
        alert("Something went wrong updating the reply.");
      }
    });

    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
  }

  async function deleteCommentFlow(commentId) {
    const ok = confirm("Delete this reply? This cannot be undone.");
    if (!ok) return;

    try {
      const fd = new FormData();
      fd.append("comment_id", String(commentId));

      const res = await fetch("actions/delete_comment.php", {
        method: "POST",
        body: fd,
        credentials: "include",
      });

      const data = await res.json();
      if (!data.success) {
        alert(data.message || "Could not delete reply.");
        return;
      }

      // refresh comments list
      const comRes = await fetch(`actions/fetch_comments.php?post_id=${encodeURIComponent(postId)}`, {
        credentials: "include",
      });
      const comData = await comRes.json();
      if (comData.success) {
        renderReplies(comData.comments || []);
        wireCommentActionButtons();
        feather.replace();
      }
    } catch (err) {
      console.error(err);
      alert("Something went wrong deleting the reply.");
    }
  }


  async function getCurrentUser() {
    const res = await fetch("../../actions/login_sync.php", { credentials: "include" });
    const data = await res.json();
    if (!data.loggedIn) return null;
    return data.user; // { user_id, email, name, role }
  }

  function addEditDeleteControlsIfAllowed(user, postObj) {
    if (!user || !postObj) return;

    const isAuthor  = String(user.user_id) === String(postObj.author_id);
    const isManager = String(user.role || "").toLowerCase() === "manager";

    const canEdit   = isAuthor;
    const canDelete = isAuthor || isManager;

    if (!canEdit && !canDelete) return;

    const header = contentEl.querySelector(".post-card-header");
    if (!header) return;

    // Use CSS, not inline styles
    const actions = document.createElement("div");
    actions.className = "post-actions";

    if (canEdit) {
      const editBtn = document.createElement("button");
      editBtn.type = "button";
      editBtn.className = "post-action-btn post-action-btn--edit";
      editBtn.textContent = "Edit";
      editBtn.addEventListener("click", () => openEditFlow(postObj));
      actions.appendChild(editBtn);
    }

    if (canDelete) {
      const delBtn = document.createElement("button");
      delBtn.type = "button";
      delBtn.className = "post-action-btn post-action-btn--delete";
      delBtn.textContent = "Delete";
      delBtn.addEventListener("click", () => deletePostFlow(postObj));
      actions.appendChild(delBtn);
    }

    header.appendChild(actions);
  }

  function applyLastEditedText(postObj) {
    // Only show if updated_at exists and is different from created_at
    const created = normalizeDateString(postObj.created_at);
    const updated = normalizeDateString(postObj.updated_at || postObj.last_edited_at);

    if (!updated) return;
    if (created && updated && created === updated) return;

    const dateEl = contentEl.querySelector(".post-card-date");
    if (!dateEl) return;

    // Keep existing format, just append a little text
    dateEl.textContent = `${dateEl.textContent} • Edited ${formatDate(updated, true)}`;
  }

  function normalizeDateString(s) {
    if (!s) return "";
    // make "YYYY-MM-DD HH:MM:SS" stable
    return String(s).trim();
  }

  async function openEditFlow(postObj) {
    // Find the post card area
    const card = contentEl.querySelector(".post-card");
    if (!card) return;

    // Prevent opening twice
    if (card.querySelector(".edit-post-inline")) return;

    // Grab current values
    const currentTitle = postObj.title || "";
    const currentContent = postObj.content || "";

    // Replace the post body with a "Create Post"-style form
    const body = card.querySelector(".post-card-body");
    if (!body) return;

    // Save original HTML so Cancel can restore it
    const originalBodyHTML = body.innerHTML;

    body.innerHTML = `
      <div class="create-post-form edit-post-inline">
        <div class="form-group">
          <label for="edit-post-title">Title</label>
          <input id="edit-post-title" type="text" value="${escapeHtml(currentTitle)}" />
        </div>

        <div class="form-group">
          <label for="edit-post-details">Details</label>
          <textarea id="edit-post-details" rows="10">${escapeHtml(currentContent)}</textarea>
        </div>

        <div class="edit-actions">
          <button type="button" class="create-post-btn" id="save-edit-btn">Save Changes</button>
          <button type="button" class="edit-cancel-btn" id="cancel-edit-btn">Cancel</button>
        </div>
      </div>
    `;

    const titleInput = card.querySelector("#edit-post-title");
    const detailsInput = card.querySelector("#edit-post-details");
    const saveBtn = card.querySelector("#save-edit-btn");
    const cancelBtn = card.querySelector("#cancel-edit-btn");

    // Cancel = restore original post body exactly
    cancelBtn.addEventListener("click", () => {
      body.innerHTML = originalBodyHTML;
    });

    // Save = call update_post.php then update UI
    saveBtn.addEventListener("click", async () => {
      const titleTrim = String(titleInput.value || "").trim();
      const contentTrim = String(detailsInput.value || "").trim();

      if (!titleTrim || !contentTrim) {
        alert("Title and content cannot be empty.");
        return;
      }

      try {
        const fd = new FormData();
        fd.append("post_id", String(postObj.post_id));
        fd.append("title", titleTrim);
        fd.append("content", contentTrim);

        const res = await fetch("actions/update_post.php", {
          method: "POST",
          body: fd,
          credentials: "include",
        });

        const data = await res.json();
        if (!data.success) {
          alert(data.message || "Could not update post.");
          return;
        }

        // Update state
        postObj.title = titleTrim;
        postObj.content = contentTrim;
        if (data.updated_at) postObj.updated_at = data.updated_at;

        // Update page title + post content
        titleEl.textContent = titleTrim;

        body.innerHTML = `<p>${formatMultiline(contentTrim)}</p>`;

        // Update edited label nicely
        const dateEl = contentEl.querySelector(".post-card-date");
        if (dateEl) dateEl.textContent = formatDate(postObj.created_at);
        applyLastEditedText(postObj);

        feather.replace();
      } catch (err) {
        console.error(err);
        alert("Something went wrong updating the post.");
      }
    });

    // focus title
    titleInput.focus();
    titleInput.setSelectionRange(titleInput.value.length, titleInput.value.length);
  }



  async function deletePostFlow(postObj) {
    const ok = confirm("Are you sure you want to delete this post? This cannot be undone.");
    if (!ok) return;

    try {
      const fd = new FormData();
      fd.append("post_id", String(postObj.post_id));

      const res = await fetch("actions/delete_post.php", {
        method: "POST",
        body: fd,
        credentials: "include",
      });

      const data = await res.json();
      if (!data.success) {
        alert(data.message || "Could not delete post.");
        return;
      }

      alert("Post deleted.");
      window.location.href = "knowledge-base.html";
    } catch (err) {
      console.error(err);
      alert("Something went wrong deleting the post.");
    }
  }

  // -------------------------
  // Helpers
  // -------------------------

  function formatDate(iso, withTime = false) {
    if (!iso) return "";
    const d = new Date(String(iso).replace(" ", "T"));
    if (isNaN(d.getTime())) return String(iso);

    if (!withTime) {
      return d.toLocaleDateString("en-GB", {
        day: "numeric",
        month: "long",
        year: "numeric",
      });
    }

    return d.toLocaleDateString("en-GB", {
      day: "numeric",
      month: "long",
      year: "numeric",
    }) + " at " + d.toLocaleTimeString("en-GB", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

    function formatMultiline(text) {
    // Escape first, then replace newlines with <br>
    return escapeHtml(text).replace(/\n/g, "<br>");
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  async function fetchTopics() {
    const res = await fetch("actions/fetch_topics.php", { credentials: "include" });
    const data = await res.json();
    if (!data.success) return [];
    return data.topics || [];
  }

  function shuffle(arr) {
    const a = arr.slice();
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  async function renderRelatedTopics({ currentTopicName }) {
    const widget = document.getElementById("related-topics-widget");
    const el = document.getElementById("related-topics-list");

    // If the post page doesn't have the widget, do nothing
    if (!widget || !el) return;

    const topics = await fetchTopics();

    const names = topics
      .filter(t => String(t.is_public) === "1")
      .map(t => String(t.topic_name || "").trim())
      .filter(Boolean);

    const other = names.filter(n => n !== currentTopicName);

    // pick up to 3 random topics
    const picks = shuffle(other).slice(0, 3);

    // if there are no other topics, show the current topic only
    const finalList = picks.length ? picks : [currentTopicName].filter(Boolean);

    // if still nothing, hide widget
    if (!finalList.length) {
      widget.style.display = "none";
      return;
    }

    // show widget when we have something to show
    widget.style.display = "";

    el.innerHTML = finalList
      .map(name => {
        const safeText = escapeHtml(name);
        return `<a href="knowledge-base.html" class="topic-tag" data-topic="${safeText}">${safeText}</a>`;
      })
      .join("");

    // Save for KB page
    el.querySelectorAll("a[data-topic]").forEach(a => {
      a.addEventListener("click", () => {
        sessionStorage.setItem("returnToTopic", a.textContent || "");
      });
    });
  }

})();

async function wireSinglePostLike() {
  const btn = document.querySelector(".kb-like-btn");
  if (!btn) return;

  const countEl = btn.querySelector(".kb-like-count");
  const postId = parseInt(btn.dataset.postId || "0", 10);
  if (!postId) return;

  btn.addEventListener("click", async (e) => {
    e.preventDefault();

    btn.disabled = true;

    try {
      const fd = new FormData();
      fd.append("post_id", String(postId));

      const res = await fetch(`${KB_ACTIONS_BASE}/like_post.php`, {
        method: "POST",
        body: fd,
        credentials: "include"
      });

      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Like failed");

      if (countEl) countEl.textContent = String(data.like_count);

      // Toggle liked state
      if (data.liked) {
        btn.classList.add("liked");
      } else {
        btn.classList.remove("liked");
      }
    } catch (err) {
      console.error(err);
      alert("Could not like post. Please try again.");
    } finally {
      btn.disabled = false;
      feather.replace();
    }
  });
}

function wireMarkSolved() {
  const btn = document.getElementById("kb-mark-solved");
  if (!btn) return;

  btn.addEventListener("click", async (e) => {
    e.preventDefault();

    const postId = btn.dataset.postId;
    if (!postId) return;

    btn.disabled = true;

    try {
      const fd = new FormData();
      fd.append("post_id", String(postId));

      const res = await fetch("actions/mark_solved.php", {
        method: "POST",
        body: fd,
        credentials: "include"
      });

      if (!res.ok) throw new Error("HTTP " + res.status);

      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("mark_solved.php returned non-JSON:", text);
        throw new Error("Bad JSON response");
      }

      if (!data.success) throw new Error(data.message || "Failed");


      // add badge if not already there
      const footer = contentEl.querySelector(".post-card-footer");
      if (footer && !footer.querySelector(".kb-solved-badge")) {
        const badge = document.createElement("span");
        badge.className = "kb-solved-badge";
        badge.textContent = "Solved";
        footer.appendChild(badge);
      }

      // remove the button
      btn.remove();

      // update local state (optional)
      post.is_solved = 1;

      feather.replace();
    } catch (err) {
      console.error(err);
      alert("Marked as solved.");
      btn.disabled = false;
    }
  });
}
