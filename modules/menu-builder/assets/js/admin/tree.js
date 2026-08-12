/**
 * CGS MB Admin — Tree ops module (v4.10.84)
 * Pure helpers; state object injected by orchestrator.
 */
(function (w) {
  'use strict';
  w.CGS_MB_Modules = w.CGS_MB_Modules || {};

  function findById(items, id) {
    for (var i = 0; i < (items || []).length; i++) {
      if (items[i].id === id) return items[i];
      var f = findById(items[i].children || [], id);
      if (f) return f;
    }
    return null;
  }

  function removeById(items, id) {
    for (var i = 0; i < (items || []).length; i++) {
      if (items[i].id === id) { items.splice(i, 1); return true; }
      if (removeById(items[i].children || [], id)) return true;
    }
    return false;
  }

  function flatten(items, depth) {
    depth = depth || 0;
    var out = [];
    (items || []).forEach(function (it) {
      var copy = {};
      for (var k in it) { if (Object.prototype.hasOwnProperty.call(it, k) && k !== 'children') copy[k] = it[k]; }
      copy.depth = depth;
      out.push(copy);
      if (it.children && it.children.length) {
        out = out.concat(flatten(it.children, depth + 1));
      }
    });
    return out;
  }

  function unflatten(flat) {
    var roots = [];
    var stack = [];
    (flat || []).forEach(function (it) {
      var node = {};
      for (var k in it) { if (Object.prototype.hasOwnProperty.call(it, k) && k !== 'depth' && k !== 'children') node[k] = it[k]; }
      node.children = [];
      var d = parseInt(it.depth, 10) || 0;
      if (d === 0) {
        roots.push(node);
        stack = [node];
      } else {
        while (stack.length > d) stack.pop();
        var parent = stack[stack.length - 1];
        if (parent) parent.children.push(node);
        else roots.push(node);
        stack[d] = node;
        stack.length = d + 1;
      }
    });
    return roots;
  }

  w.CGS_MB_Modules.tree = {
    maxDepth: 5,
    findById: findById,
    removeById: removeById,
    flatten: flatten,
    unflatten: unflatten
  };
})(window);
