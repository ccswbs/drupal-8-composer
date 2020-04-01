using System;
using Microsoft.Extensions.Configuration;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using Test;


using OpenQA.Selenium;
using OpenQA.Selenium.Chrome;
using OpenQA.Selenium.Remote;
using OpenQA.Selenium.Support.UI;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Threading;

namespace BoveyTest
{
    [TestClass]
    public class FrontEndCI : DrupalTest
    {
        private string _adminUser;
        private string _adminPass;
        private string _frontEndEnv = "preview_changes";
        private string _frontEndEnvTitle = "Preview Changes environment deployment";

        [TestInitialize]
        [DeploymentItem("appsettings*.json")]
        public void Initialize()
        {
            var config = new ConfigurationBuilder()
                .AddJsonFile("appsettings.json")
                .AddJsonFile("appsettings.local.json", optional:true)
                .Build();
            base.Initialize(config["TestQAHostname"], config["basePath"]);
            _adminUser = config["TestQAUsername"];
            _adminPass = config["TestQAPassword"];
            DrupalLogin(_adminUser, _adminPass);
        }

        [TestCleanup]
        override public void Cleanup()
        {
            _driver.Quit();
        }

        [TestMethod]
        public void BuildHookSuccessful()
        {
            var buildHookSubmitBtn = "edit-submit";
            string[] permittedRoles = new string[] {"publisher"};

            TurnOnLDAPMixedMode();

            foreach (string role in permittedRoles){
                // Create test user for each permitted role
                DrupalUser testUser = CreateUser(new string[] {role});
                DrupalLogout();
                DrupalLogin(testUser.Name, testUser.Password);

                // Go to Build Hook deployment page
                DrupalGet("/admin/build_hooks/deployments/" + _frontEndEnv);

                // Activate build hook
                Click(buildHookSubmitBtn);

                // Check if successful message appears after testing connection
                var successfulBuildHookMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,'Deployment triggered for environment')]]");
                Assert.AreEqual(successfulBuildHookMessage.Count, 1);

                // Delete test user
                DrupalLogout();
                DrupalLogin(_adminUser, _adminPass);
                DeleteUser(testUser.Name, true);
            }

            TurnOffLDAPMixedMode();
        }

        [TestMethod]
        public void BuildHookOnlyVisibleForPublisherAndAdmin()
        {
            string[] permittedRoles = new string[] {"administrator","publisher"};

            // Set up
            TurnOnLDAPMixedMode();
            DrupalUser testUser = CreateUser(permittedRoles);
            DrupalLogout();
            DrupalLogin(testUser.Name, testUser.Password);

            // Confirm that build hook is visible for permitted roles
            DrupalGet("/admin/build_hooks/deployments/" + _frontEndEnv);
            Assert.AreEqual(CheckIfPageTitleIsCorrect(_frontEndEnvTitle),true);
            
            // Update user to have all roles except permitted roles
            DrupalLogout();
            DrupalLogin(_adminUser,_adminPass);
            AddRoles(testUser);
            RemoveRoles(testUser, permittedRoles);
            DrupalLogout();
            DrupalLogin(testUser.Name, testUser.Password);

            // Confirm that build hook is not visible for permitted roles
            DrupalGet("/admin/build_hooks/deployments/" + _frontEndEnv);
            Assert.AreEqual(CheckIfPageTitleIsCorrect("Access denied"),true);

            // Clean up
            DrupalLogout();
            DrupalLogin(_adminUser,_adminPass);
            DeleteUser(testUser.Name, true);
            TurnOffLDAPMixedMode();
        }

        void DeleteUser (string name, bool deleteContent = false){
            // Go to User Edit page
            GoToEditUserPage(name);
            
            // Confirm it is the correct user
            if(CheckIfPageTitleIsCorrect(name) == true){
                Click("edit-delete");

                // adjust delete content settings
                if(deleteContent == true){
                    Click("edit-user-cancel-method-user-cancel-delete");
                }else{
                    Click("edit-user-cancel-method-user-cancel-reassign");
                }

                Click("edit-submit");
            }
        }
        
        // If no roles are provided, by default, method removes all available roles
        void RemoveRoles(DrupalUser user, string[] roles = null){
            GoToEditUserPage(user.Name);

            if(roles == null){
                // Remove all roles
                var checkboxes = Driver.FindElementsByXPath($"//input[contains(@id,'edit-roles-') and contains(@checked,'checked')]");
                foreach(var checkbox in checkboxes){
                    ScrollAndClick(checkbox);
                }
            }else{
                // Remove specific roles
                foreach (var role in roles)
                {
                    var checkbox = Driver.FindElementByXPath($"//input[@id='edit-roles-{role}' and contains(@checked,'checked')]");
                    ScrollAndClick(checkbox);
                }
            }
            Click("edit-submit");
        }

        // If no roles are provided, by default, method adds all available roles
        void AddRoles(DrupalUser user, string[] roles = null){
            GoToEditUserPage(user.Name);
            if(roles == null){
                // Add all roles
                var checkboxes = Driver.FindElementsByXPath($"//input[contains(@id,'edit-roles-') and not(contains(@checked,'checked'))]");
                foreach(var checkbox in checkboxes){
                    ScrollAndClick(checkbox);
                }
            }else{
                // Add specific roles
                foreach (var role in roles)
                {
                    var checkbox = Driver.FindElementByXPath($"//input[@id='edit-roles-{role}' and not(contains(@checked,'checked'))]");
                    ScrollAndClick(checkbox);
                }
            }
            Click("edit-submit");
        }

        void GoToEditUserPage(string name){
            DrupalGet("/admin/people");
            Driver.FindElementByXPath($"//a[@content='{name}']").Click();
            Driver.FindElementByXPath($"//a[text()='Edit']").Click();
        }

        bool CheckIfPageTitleIsCorrect(string title){
            var heading = Driver.FindElementsByXPath($"//h1[contains(@class,'page-title') and (contains(text(),'{title}'))]");
            if (heading.Count() == 1){
                return true;
            }
            return false;
        }

        DrupalUser CreateUser(string[] roles = null, bool blocked = false)
        {
            var name = "selenium-user-" + RandomName();
            var mail = name + "@example.com";
            var pass = RandomString(16);

            DrupalGet("/admin/people/create");

            Driver.FindElementByName("name").SendKeys(name);
            Driver.FindElementByName("mail").SendKeys(mail);
            Driver.FindElementByName("pass[pass1]").SendKeys(pass);
            Driver.FindElementByName("pass[pass2]").SendKeys(pass);
            if (blocked)
            {
                Click("edit-status-0");
            }

            if (roles != null){
                foreach (var role in roles)
                {
                    var checkbox = Driver.FindElementByXPath($"//label[@for='edit-roles-{role}']");
                    ScrollAndClick(checkbox);
                }
            }
            Click("edit-submit");
            return new DrupalUser { Name = name, Password = pass };
        }

        void TurnOnLDAPMixedMode(){
            DrupalGet("/admin/config/people/ldap/authentication");
            var checkbox = Driver.FindElementByXPath($"//input[@id='edit-authenticationmode-1']");
            ScrollAndClick(checkbox);
            Click("edit-submit");
        }

        void TurnOffLDAPMixedMode(){
            DrupalGet("/admin/config/people/ldap/authentication");
            var checkbox = Driver.FindElementByXPath($"//input[@id='edit-authenticationmode-2']");
            ScrollAndClick(checkbox);
            Click("edit-submit");
        }

        void ScrollIntoView(IWebElement element)
        {
            ((IJavaScriptExecutor)Driver).ExecuteScript("arguments[0].scrollIntoView(true)", element);
        }

        public void ScrollIntoView(SelectElement element)
        {
            ScrollIntoView(element.WrappedElement);
        }

        void ScrollAndClick(IWebElement element){
            ScrollIntoView(element);
            ((IJavaScriptExecutor)Driver).ExecuteScript("arguments[0].click()", element);
        }

    }
}
