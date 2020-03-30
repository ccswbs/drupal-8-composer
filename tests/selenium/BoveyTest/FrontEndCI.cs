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
        public void BuildTriggerSuccessful()
        {
            var frontEndEnv = "preview_changes";
            var buildTriggerSubmitBtn = "edit-submit";
            string[] permittedRoles = new string[] {"publisher"};

            TurnOnLDAPMixedMode();

            foreach (string role in permittedRoles){
                // Create test user for each permitted role
                DrupalUser testUser = CreateUser(new string[] {role});
                DrupalLogout();
                DrupalLogin(testUser.Name, testUser.Password);

                // Go to Build Trigger deployment page
                DrupalGet("/admin/build_hooks/deployments/" + frontEndEnv);

                // Activate build trigger
                Click(buildTriggerSubmitBtn);

                // Check if successful message appears after testing connection
                var successfulBuildTriggerMessage = Driver.FindElementsByXPath($"//div[contains(@class, 'messages--status') and text()[contains(.,'Deployment triggered for environment')]]");
                Assert.AreEqual(successfulBuildTriggerMessage.Count, 1);

                // Delete test user
                DrupalLogout();
                DrupalLogin(_adminUser, _adminPass);
                DeleteUser(testUser.Name, true);
            }

            TurnOffLDAPMixedMode();
        }

        [TestMethod]
        public void BuildTriggerOnlyVisibleForPublisherAndAdmin()
        {
            var permissionIDStub = "trigger-deployments";
            string[] permittedRoles = new string[] {"administrator", "publisher"};
            
            // Go to Permissions page
            DrupalGet("/admin/people/permissions");

            // Check if permitted roles have permission
            foreach (string role in permittedRoles){
                var adminPermission = Driver.FindElementsByXPath($"//input[contains(@id, 'edit-{role}-{permissionIDStub}') and contains(@checked, 'checked')]");
                Assert.AreEqual(adminPermission.Count, 1);
            }

            // Check that no other roles have permission
            var numberOfRolesWithPermission = Driver.FindElementsByXPath($"//input[contains(@id, '-{permissionIDStub}') and contains(@checked, 'checked')]");
            Assert.AreEqual(numberOfRolesWithPermission.Count, permittedRoles.Length);
        }

        public void DeleteUser (string name, bool deleteContent = false){
            // Go to User Edit page
            DrupalGet("/admin/people");
            Driver.FindElementByXPath($"//a[@content='{name}']").Click();
            Driver.FindElementByXPath($"//a[text()='Edit']").Click();

            // Confirm it is the correct user
            var userToDelete = Driver.FindElementsByXPath($"//h1[text()='{name}']");
            if(userToDelete != null){
                Click("edit-delete");
            }

            // adjust delete content settings
            if(deleteContent == true){
                Click("edit-user-cancel-method-user-cancel-delete");
            }else{
                Click("edit-user-cancel-method-user-cancel-reassign");
            }

            Click("edit-submit");
        }
        
        public DrupalUser CreateUser(string[] roles, bool blocked = false)
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
            foreach (var role in roles)
            {
                var checkbox = Driver.FindElementByXPath($"//label[@for='edit-roles-{role}']");
                ScrollAndClick(checkbox);
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
