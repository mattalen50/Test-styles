class DisclosureNav {
  constructor(domNode) {
    this.rootNode = domNode;
    this.controlledNodes = [];
    this.openIndex = null;
    this.parentIndex = null;
    this.useArrowKeys = true;
    this.menuNodes = [
      ...this.rootNode.querySelectorAll(
        '.top-level, .has-submenu, button[aria-expanded][aria-controls]'
      ),
    ];
    if (this.menuNodes.length > 0) {
      // Elements we care about.
      // Hierarchal links with an associated submenu, & button triggers
      this.menuNodes.forEach((node, index) => {
        // if it's a button & controls a ul submenu
        if (node.tagName.toLowerCase() === 'button' && node.hasAttribute('aria-controls')) {

          // Add the button's index as a data attribute data-buttonindex=i
          node.dataset.buttonindex = index;

          // node.parentNode in this context is the containing li
          // ul is the child submenu (after button) relative to the li
          const menu = node.parentNode.querySelector('ul');

          if (menu) {
            // add menu's index as data-menuindex=i
            menu.dataset.menuindex = index;
            // save ref controlled menu
            // push child ul to controlledNodes array
            this.controlledNodes.push(menu);

            // collapse menus
            node.setAttribute('aria-expanded', 'false');
            this.menuDisplay(menu, false);

            // attach event listeners
            // Keyboard interaction with elements in an OPENED menu
            menu.addEventListener('keydown', this.onMenuKeyDown.bind(this));
            // Mouse click handler
            node.addEventListener('click', this.onButtonClick.bind(this));
            // Interaction with menu BUTTON
            node.addEventListener('keydown', this.onButtonKeyDown.bind(this));
          }
        }
        // If element is not a button, it's a regular link
        // add a null value to the controlledNodes array
        // Listen for keyboard interaction on the LINK
        else {
          this.controlledNodes.push(null);
          node.addEventListener('keydown', this.onLinkKeyDown.bind(this));
        }
      });
    }

    // Listener for focusing outside of menus
    this.rootNode.addEventListener('focusout', this.onBlur.bind(this));
  }

  controlFocusByKey(keyboardEvent, nodeList, currentIndex) {
    switch (keyboardEvent.key) {
      case 'ArrowUp':
      case 'ArrowLeft':
        keyboardEvent.preventDefault();
        if (currentIndex > -1) {
          let prevIndex = Math.max(0, currentIndex - 1);
          nodeList[prevIndex].focus();
        }
        break;
      case 'ArrowDown':
      case 'ArrowRight':
        keyboardEvent.preventDefault();
        if (currentIndex > -1) {
          let nextIndex = Math.min(nodeList.length - 1, currentIndex + 1);
          nodeList[nextIndex].focus();
        }
        break;
      case 'Home':
        keyboardEvent.preventDefault();
        nodeList[0].focus();
        break;
      case 'End':
        keyboardEvent.preventDefault();
        nodeList[nodeList.length - 1].focus();
        break;
    }
  }

  onBlur(event) {
    let menuContainsFocus = this.rootNode.contains(event.relatedTarget);
    if (!menuContainsFocus && this.openIndex !== null) {
      this.toggleExpand(this.openIndex, false);
      if (this.parentIndex !== null && this.openIndex !== null) {
        this.toggleExpand(this.parentIndex, false);
      }
    }
  }

  onButtonClick(event) {
    let button = event.target;
    let buttonIndex = this.menuNodes.indexOf(button);
    let buttonAriaExpanded = button.getAttribute('aria-expanded') === 'true';
    this.toggleExpand(buttonIndex, !buttonAriaExpanded);
  }

  onButtonKeyDown(event) {
    let targetButtonIndex = this.menuNodes.indexOf(document.activeElement);

    // close on escape
    if (event.key === 'Escape') {
      this.toggleExpand(this.openIndex, false);
    }

    // move focus into the open menu if the current menu is open
    else if (
      this.useArrowKeys &&
      this.openIndex === targetButtonIndex &&
      event.key === 'ArrowDown'
    ) {
      event.preventDefault();
      this.controlledNodes[this.openIndex].querySelector('a').focus();
    }

    // handle arrow key navigation between top-level buttons, if set
    else if (this.useArrowKeys) {
      this.controlFocusByKey(event, this.menuNodes, targetButtonIndex);
    }
  }

  onLinkKeyDown(event) {
    let targetLinkIndex = this.menuNodes.indexOf(document.activeElement);

    // handle arrow key navigation between top-level buttons, if set
    if (this.useArrowKeys) {
      this.controlFocusByKey(event, this.menuNodes, targetLinkIndex);
    }
  }

  onMenuKeyDown(event) {
    if (this.openIndex === null) {
      return;
    }

    let menuLinks = Array.prototype.slice.call(
      this.controlledNodes[this.openIndex].querySelectorAll('a')
    );
    let currentIndex = menuLinks.indexOf(document.activeElement);

    // close on escape
    if (event.key === 'Escape') {
      this.menuNodes[this.openIndex].focus();
      this.toggleExpand(this.openIndex, false);
    }

    // handle arrow key navigation within menu links, if set
    else if (this.useArrowKeys) {
      this.controlFocusByKey(event, menuLinks, currentIndex);
    }
  }

  toggleExpand(index, expand) {
    // Short circuit if there is no value for index
    // This shouldn't normally happen but using Escape key twice sometimes triggers JS error
    if (!index) {
      return;
    }

    // Define if we're in a level 1 menu or not
    const levelOne = this.controlledNodes[index].classList.contains("menu-depth-1") ? true : false;

    // Close everything if...
    // The open menu isn't the current index
    // openIndex isn't null - we don't need to close anything if nothing is open...
    // We're interacting with a main menu toggle (level 0), controlling a level 1 menu
    // Additionally, if we're doing a hard close on currently open menu, and that's a secondary menu, all fine, but we also need to close the parent too
    if (this.openIndex !== index && this.openIndex !== null && levelOne) {
      this.toggleExpand(this.openIndex, false);
      if (this.parentIndex !== null) {
        this.menuNodes[this.parentIndex].setAttribute('aria-expanded', false);
        this.menuDisplay(this.controlledNodes[this.parentIndex], false);
      }
    }

    // This triggers before a level 2 submenu SECONDARY OPEN. We want to close Level Two submenus if...
    // We're expanding something which is also a LEVEL 2
    // parentIndex !== openIndex. If this is the FIRST interaction with a Level 2 submenu, these values will be the same
    if (expand && !levelOne && this.parentIndex !== this.openIndex) {
      this.menuNodes[this.openIndex].setAttribute('aria-expanded', false);
      this.menuDisplay(this.controlledNodes[this.openIndex], false);
    }

    // handle menu at called index
    if (this.menuNodes[index]) {

      // if we're toggling closed (!expand) an OPEN level 2 menu, we need to set openIndex to the closed menu's parent
      if (expand) {
        this.openIndex = index
      } else {
        if (levelOne) {
          this.openIndex = null;
        } else {
          this.openIndex = this.parentIndex;
        }
      }

      if (levelOne && expand) this.parentIndex = this.openIndex;
      this.menuNodes[index].setAttribute('aria-expanded', expand);
      this.menuDisplay(this.controlledNodes[index], expand);
    }
  }

  menuDisplay(domNode, show) {
    if (domNode) {
      domNode.style.display = show ? 'block' : 'none';
    }
  }
}

// Initialize menus
window.addEventListener('DOMContentLoaded', () => {
  let menus = document.querySelectorAll('.disclosure-nav');
  let disclosureMenus = [];

  menus.forEach(function (menu) {
    disclosureMenus[menu] = new DisclosureNav(menu);

    // // Helper classes for menu items
    // const rightLinks = menu.querySelectorAll('.button-right');
    // const maroonButtons = menu.querySelectorAll('.button-maroon');
    // const goldButtons = menu.querySelectorAll('.button-gold');
    // const grayButtons = menu.querySelectorAll('.button-gray');

    // // Drupal adds the class added to the menu item to the a. We want it applied to the parent li instead
    // if (rightLinks.length > 0) {
    //   rightLinks.forEach((node, index) => {
    //     const parentLi = node.parentNode;
    //     const subMenu1 = parentLi.querySelector('.menu-depth-1');
    //     const subMenu2 = parentLi.querySelectorAll('.menu-depth-2');

    //     parentLi.classList.add('menu-item--right');

    //     if (subMenu2.length > 0) {
    //       subMenu1.classList.add('menu-item--has-submenu');
    //     }
    //   });
    // }

    // if (maroonButtons.length > 0) {
    //   maroonButtons.forEach((btn) => {
    //     btn.parentNode.classList.add('menu-item--maroon');
    //   });
    // }

    // if (goldButtons.length > 0) {
    //   goldButtons.forEach((btn) => {
    //     btn.parentNode.classList.add('menu-item--gold');
    //   });
    // }

    // if (grayButtons.length > 0) {
    //   grayButtons.forEach((btn) => {
    //     btn.parentNode.classList.add('menu-item--gray');
    //   });
    // }
  });

}, false);