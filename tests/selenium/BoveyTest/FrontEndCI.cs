using Microsoft.Extensions.Configuration;
using Microsoft.VisualStudio.TestTools.UnitTesting;
using Test;

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
                DrupalGet("admin/build_hooks/deployments/" + _frontEndEnv);

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
            DrupalGet("admin/build_hooks/deployments/" + _frontEndEnv);
            Assert.AreEqual(CheckIfPageTitleIsCorrect(_frontEndEnvTitle),true);
            
            // Update user to have all roles except permitted roles
            DrupalLogout();
            DrupalLogin(_adminUser,_adminPass);
            AddRoles(testUser);
            RemoveRoles(testUser, permittedRoles);
            DrupalLogout();
            DrupalLogin(testUser.Name, testUser.Password);

            // Confirm that build hook is not visible for permitted roles
            DrupalGet("admin/build_hooks/deployments/" + _frontEndEnv);
            Assert.AreEqual(CheckIfPageTitleIsCorrect("Access denied"),true);

            // Clean up
            DrupalLogout();
            DrupalLogin(_adminUser,_adminPass);
            DeleteUser(testUser.Name, true);
            TurnOffLDAPMixedMode();
        }
    }
}
